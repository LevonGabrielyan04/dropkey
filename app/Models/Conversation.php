<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_one_id
 * @property int $user_two_id
 * @property Carbon $created_at
 */
#[Fillable(['user_one_id', 'user_two_id'])]
class Conversation extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    /**
     * @return HasMany<ChatMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function includesUser(User $user): bool
    {
        return $this->user_one_id === $user->id || $this->user_two_id === $user->id;
    }

    public function partnerFor(User $user): User
    {
        if ($this->user_one_id === $user->id) {
            return $this->userTwo;
        }

        return $this->userOne;
    }

    /**
     * Find or create a canonical 1v1 conversation between two users.
     */
    public static function findOrCreateForUsers(User $first, User $second): self
    {
        if ($first->id === $second->id) {
            throw new \InvalidArgumentException('Cannot create a conversation with yourself.');
        }

        [$userOneId, $userTwoId] = $first->id < $second->id
            ? [$first->id, $second->id]
            : [$second->id, $first->id];

        return self::query()->firstOrCreate([
            'user_one_id' => $userOneId,
            'user_two_id' => $userTwoId,
        ]);
    }
}
