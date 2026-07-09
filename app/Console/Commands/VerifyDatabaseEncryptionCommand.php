<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Database\TransparentDataEncryptionVerifier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('db:verify-encryption {--fail-on-error : Exit with a non-zero status when encryption is incomplete}')]
#[Description('Verify MariaDB transparent data encryption (TDE) is active in production')]
class VerifyDatabaseEncryptionCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TransparentDataEncryptionVerifier $verifier): int
    {
        if (! $verifier->supportsTransparentDataEncryption()) {
            $this->warn('Transparent data encryption checks apply only to MariaDB/MySQL connections.');

            return self::SUCCESS;
        }

        $issues = $verifier->issues();

        if ($issues === []) {
            $this->info('Database transparent data encryption is fully enabled.');

            return self::SUCCESS;
        }

        foreach ($issues as $issue) {
            $this->error($issue);
        }

        if ($this->option('fail-on-error')) {
            return self::FAILURE;
        }

        $this->warn('Database encryption verification reported issues.');

        return self::SUCCESS;
    }
}
