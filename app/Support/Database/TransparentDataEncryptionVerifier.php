<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransparentDataEncryptionVerifier
{
    /**
     * @return list<string>
     */
    public function issues(): array
    {
        if (! $this->supportsTransparentDataEncryption()) {
            return [];
        }

        $issues = [];

        if (! $this->keyManagementPluginIsLoaded()) {
            $issues[] = 'The file_key_management plugin is not loaded.';
        }

        foreach ($this->requiredGlobalVariables() as $variable => $expected) {
            $actual = $this->globalVariable($variable);

            if ($actual === null) {
                $issues[] = "Missing global variable {$variable}.";

                continue;
            }

            if (! in_array(Str::upper($actual), $expected, true)) {
                $issues[] = "Expected {$variable} to be one of [".implode(', ', $expected)."], got {$actual}.";
            }
        }

        $unencryptedTablespaces = $this->unencryptedTablespaces();

        if ($unencryptedTablespaces->isNotEmpty()) {
            $issues[] = 'Unencrypted InnoDB tablespaces remain: '.$unencryptedTablespaces->implode(', ').'.';
        }

        return $issues;
    }

    public function supportsTransparentDataEncryption(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    public function isFullyEncrypted(): bool
    {
        if (! $this->supportsTransparentDataEncryption()) {
            return true;
        }

        return $this->issues() === [];
    }

    public function keyManagementPluginIsLoaded(): bool
    {
        if (! $this->supportsTransparentDataEncryption()) {
            return false;
        }

        $plugin = DB::selectOne(
            'SELECT PLUGIN_STATUS AS status
             FROM information_schema.PLUGINS
             WHERE PLUGIN_NAME = ?
             LIMIT 1',
            ['file_key_management'],
        );

        return $plugin !== null && Str::upper((string) $plugin->status) === 'ACTIVE';
    }

    /**
     * @return array<string, list<string>>
     */
    public function requiredGlobalVariables(): array
    {
        return [
            'innodb_encrypt_tables' => ['ON', 'FORCE'],
            'innodb_encrypt_log' => ['ON', '1'],
            'innodb_encrypt_temporary_tables' => ['ON', '1'],
            'aria_encrypt_tables' => ['ON', '1'],
            'encrypt_tmp_disk_tables' => ['ON', '1'],
            'encrypt_tmp_files' => ['ON', '1'],
        ];
    }

    public function globalVariable(string $variable): ?string
    {
        if (! $this->supportsTransparentDataEncryption()) {
            return null;
        }

        if (! $this->isValidGlobalVariableName($variable)) {
            return null;
        }

        $result = DB::selectOne("SELECT @@GLOBAL.{$variable} AS value");

        if ($result === null) {
            return null;
        }

        return (string) ($result->value ?? '');
    }

    protected function isValidGlobalVariableName(string $variable): bool
    {
        return (bool) preg_match('/^[a-z_][a-z0-9_]*$/i', $variable);
    }

    /**
     * @return Collection<int, non-falsy-string>
     */
    public function unencryptedTablespaces(): Collection
    {
        if (! $this->supportsTransparentDataEncryption()) {
            return collect();
        }

        if (! $this->tablespaceEncryptionViewExists()) {
            return collect();
        }

        try {
            return collect(DB::select(
                'SELECT NAME
             FROM information_schema.INNODB_TABLESPACES_ENCRYPTION
             WHERE ENCRYPTION_SCHEME = 0
               AND NAME NOT LIKE ?',
                ['mysql/%'],
            ))->map(fn (object $row): string => (string) ($row->NAME ?? $row->name ?? ''))
                ->filter()
                ->values();
        } catch (QueryException $exception) {
            if ($this->isProcessPrivilegeDenied($exception)) {
                return collect();
            }

            throw $exception;
        }
    }

    protected function isProcessPrivilegeDenied(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'PROCESS');
    }

    protected function tablespaceEncryptionViewExists(): bool
    {
        $view = DB::selectOne(
            'SELECT TABLE_NAME AS name
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
             LIMIT 1',
            ['information_schema', 'INNODB_TABLESPACES_ENCRYPTION'],
        );

        return $view !== null;
    }
}
