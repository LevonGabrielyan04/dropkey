<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\DeleteExpiredChatMessagesAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('chat-messages:delete-expired')]
#[Description('Permanently delete chat messages older than the retention period')]
class DeleteExpiredChatMessagesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(DeleteExpiredChatMessagesAction $action): int
    {
        $deletedCount = $action->execute();

        $this->info("Deleted {$deletedCount} expired chat message(s).");

        return self::SUCCESS;
    }
}
