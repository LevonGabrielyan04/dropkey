<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\DTOs\SendData;
use App\Models\Send;
use App\Repositories\Interfaces\SendRepositoryInterface;
use App\Support\SendIndexColumns;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

readonly class CachedSendsRepository implements SendRepositoryInterface
{
    private const string SEND_NOT_FOUND = '__send_not_found__';

    private readonly int $cacheTtl;

    private readonly int $negativeCacheTtl;

    public function __construct(
        private SendRepositoryInterface $repository,
        private CacheRepository $cache
    ) {
        $this->cacheTtl = (int) config('send.cache_ttl');
        $this->negativeCacheTtl = (int) config('send.negative_cache_ttl');
    }

    /**
     * {@inheritDoc}
     */
    public function find(string $id): ?Send
    {
        $cacheKey = "send_{$id}";

        $cached = $this->rememberLocked(
            $cacheKey,
            fn () => $this->loadSendForCache($id),
            fn (mixed $value): CarbonInterface => $this->cacheExpiresAtForCachedSend($value),
        );

        return $this->resolveCachedSend($cached, $cacheKey, $id);
    }

    /**
     * {@inheritDoc}
     */
    public function findAll(string $userId, array $columns): Collection
    {
        $cacheKey = $this->sendsCacheKey($userId, $columns);
        $ttl = now()->addMinutes($this->cacheTtl);

        return $this->resolveCachedSendCollection(
            $this->rememberLocked(
                $cacheKey,
                fn (): Collection => $this->repository->findAll($userId, $columns),
                fn (): CarbonInterface => $ttl,
            ),
            $cacheKey,
            $userId,
            $columns,
            $ttl,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function create(SendData $data, array $pivotData = []): Send
    {
        $send = $this->repository->create($data, $pivotData);
        $this->cache->put("send_{$send->id}", $send, $this->cacheExpiresAt($send));
        $this->forgetUserSends((string) $send->user_id, SendIndexColumns::COLUMNS);
        $this->forgetActiveSendsCount((string) $send->user_id);

        return $send;
    }

    /**
     * {@inheritDoc}
     */
    public function update(string $id, SendData $data, array $pivotData = []): Send
    {
        $sendBefore = $this->repository->find($id);
        $result = $this->repository->update($id, $data, $pivotData);
        $this->cache->put("send_{$id}", $result, $this->cacheExpiresAt($result));
        $this->forgetUserSends((string) $result->user_id, SendIndexColumns::COLUMNS);
        $this->forgetActiveSendsCount((string) $result->user_id);
        $this->forgetActiveAuthorizedAccessForSend($sendBefore);
        $this->forgetActiveAuthorizedAccessForSend($result);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $id): bool
    {
        $send = $this->repository->find($id);
        $this->cache->forget("send_{$id}");

        if ($send !== null) {
            $this->forgetUserSends((string) $send->user_id, SendIndexColumns::COLUMNS);
            $this->forgetActiveSendsCount((string) $send->user_id);
            $this->forgetActiveAuthorizedAccessForSend($send);
        }

        return $this->repository->delete($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findExpired(): Collection
    {
        return $this->repository->findExpired();
    }

    public function deleteExpired(): int
    {
        $expired = $this->repository->findExpired();

        foreach ($expired as $send) {
            $this->cache->forget("send_{$send->id}");
            $this->forgetUserSends((string) $send->user_id, SendIndexColumns::COLUMNS);
            $this->forgetActiveSendsCount((string) $send->user_id);
            $this->forgetActiveAuthorizedAccessForSend($this->repository->find($send->id));
        }

        if ($expired->isEmpty()) {
            return 0;
        }

        return $this->repository->deleteExpired();
    }

    /**
     * {@inheritDoc}
     */
    public function countActiveForUser(string $userId): int
    {
        $cacheKey = $this->activeSendsCountCacheKey($userId);
        $expiresAt = $this->cacheExpiresAtForActiveCount($userId);

        return $this->rememberLocked(
            $cacheKey,
            fn (): int => $this->repository->countActiveForUser($userId),
            fn (): CarbonInterface => $expiresAt,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function userHasActiveAuthorizedAccess(string $userId, string $sendId): bool
    {
        $cacheKey = $this->activeAuthorizedAccessCacheKey($userId, $sendId);
        $send = $this->find($sendId);
        $expiresAt = $send !== null
            ? $this->cacheExpiresAt($send)
            : now()->addMinutes($this->negativeCacheTtl);

        return $this->rememberLocked(
            $cacheKey,
            fn (): bool => $this->repository->userHasActiveAuthorizedAccess($userId, $sendId),
            fn (): CarbonInterface => $expiresAt,
        );
    }

    private function loadSendForCache(string $id): Send|string
    {
        $send = $this->repository->find($id);

        if ($send === null) {
            return self::SEND_NOT_FOUND;
        }

        return $send;
    }

    private function resolveCachedSend(mixed $cached, string $cacheKey, string $id): ?Send
    {
        if ($cached === self::SEND_NOT_FOUND) {
            return null;
        }

        if ($cached instanceof Send) {
            if (Carbon::parse($cached->valid_to)->isPast()) {
                $this->cache->put(
                    $cacheKey,
                    $cached,
                    now()->addMinutes($this->negativeCacheTtl),
                );
            }

            return $cached;
        }

        $this->cache->forget($cacheKey);
        $send = $this->repository->find($id);

        if ($send === null) {
            $this->cache->put($cacheKey, self::SEND_NOT_FOUND, now()->addMinutes($this->negativeCacheTtl));

            return null;
        }

        $this->cache->put($cacheKey, $send, $this->cacheExpiresAt($send));

        return $send;
    }

    /**
     * @param  array<int, string>  $columns
     * @return Collection<int, Send>
     */
    private function resolveCachedSendCollection(
        mixed $cached,
        string $cacheKey,
        string $userId,
        array $columns,
        CarbonInterface $ttl,
    ): Collection {
        if (! $cached instanceof Collection) {
            return $this->refreshSendCollectionCache($cacheKey, $userId, $columns, $ttl);
        }

        foreach ($cached as $send) {
            if (! $send instanceof Send) {
                return $this->refreshSendCollectionCache($cacheKey, $userId, $columns, $ttl);
            }

            if (Carbon::parse($send->valid_to)->isPast()) {
                return $this->refreshSendCollectionCache($cacheKey, $userId, $columns, $ttl);
            }
        }

        return $cached;
    }

    /**
     * @param  array<int, string>  $columns
     * @return Collection<int, Send>
     */
    private function refreshSendCollectionCache(
        string $cacheKey,
        string $userId,
        array $columns,
        CarbonInterface $ttl,
    ): Collection {
        $this->cache->forget($cacheKey);
        $collection = $this->repository->findAll($userId, $columns);
        $this->cache->put($cacheKey, $collection, $ttl);

        return $collection;
    }

    /**
     * @param  Closure(): mixed  $callback
     * @param  Closure(mixed): CarbonInterface  $ttlResolver
     */
    private function rememberLocked(string $key, Closure $callback, Closure $ttlResolver): mixed
    {
        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        return $this->cache->withoutOverlapping(
            "lock:{$key}",
            function () use ($key, $callback, $ttlResolver): mixed {
                if ($this->cache->has($key)) {
                    return $this->cache->get($key);
                }

                $value = $callback();
                $this->cache->put($key, $value, $ttlResolver($value));

                return $value;
            },
            lockFor: 10,
            waitFor: 5,
        );
    }

    private function cacheExpiresAtForCachedSend(mixed $value): CarbonInterface
    {
        if ($value === self::SEND_NOT_FOUND) {
            return now()->addMinutes($this->negativeCacheTtl);
        }

        if ($value instanceof Send) {
            return $this->cacheExpiresAt($value);
        }

        return now()->addMinutes($this->cacheTtl);
    }

    private function cacheExpiresAt(Send $send): CarbonInterface
    {
        $validTo = Carbon::parse($send->valid_to);
        $ttlLimit = now()->addMinutes($this->cacheTtl);

        if ($validTo->isPast()) {
            return now()->addMinutes($this->negativeCacheTtl);
        }

        return $validTo->min($ttlLimit);
    }

    private function cacheExpiresAtForActiveCount(string $userId): CarbonInterface
    {
        $expiresAt = now()->addMinutes($this->cacheTtl);

        foreach ($this->findAll($userId, ['valid_to']) as $send) {
            if (Carbon::parse($send->valid_to)->isPast()) {
                continue;
            }

            $expiresAt = $this->cacheExpiresAt($send)->min($expiresAt);
        }

        return $expiresAt;
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function sendsCacheKey(string $userId, array $columns): string
    {
        $encodedColumns = json_encode(array_values($columns));

        if ($encodedColumns === false) {
            throw new \RuntimeException('Unable to encode columns for cache key.');
        }

        return 'sends_'.$userId.'_'.hash('xxh128', $encodedColumns);
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function forgetUserSends(string $userId, array $columns): void
    {
        $this->cache->forget("sends_{$userId}");
        $this->cache->forget($this->sendsCacheKey($userId, $columns));
    }

    private function activeSendsCountCacheKey(string $userId): string
    {
        return "active_sends_count_{$userId}";
    }

    private function forgetActiveSendsCount(string $userId): void
    {
        $this->cache->forget($this->activeSendsCountCacheKey($userId));
    }

    private function activeAuthorizedAccessCacheKey(string $userId, string $sendId): string
    {
        return "active_authorized_access_{$userId}_{$sendId}";
    }

    private function forgetActiveAuthorizedAccess(string $userId, string $sendId): void
    {
        $this->cache->forget($this->activeAuthorizedAccessCacheKey($userId, $sendId));
    }

    private function forgetActiveAuthorizedAccessForSend(?Send $send): void
    {
        if ($send === null || ! $send->relationLoaded('authorizedUsers')) {
            return;
        }

        foreach ($send->authorizedUsers as $user) {
            $this->forgetActiveAuthorizedAccess((string) $user->id, $send->id);
        }
    }
}
