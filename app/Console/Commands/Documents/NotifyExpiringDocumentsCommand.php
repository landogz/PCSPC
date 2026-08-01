<?php

namespace App\Console\Commands\Documents;

use App\Services\Documents\DocumentService;
use Illuminate\Console\Command;

class NotifyExpiringDocumentsCommand extends Command
{
    protected $signature = 'documents:notify-expiring {--days= : Override expiring-within days}';

    protected $description = 'Email HR managers a digest of documents expiring soon';

    public function handle(DocumentService $documents): int
    {
        $days = $this->option('days');
        $result = $documents->sendExpiryDigest(
            $days !== null && $days !== '' ? (int) $days : null
        );

        $this->info(sprintf(
            'Expiry digest sent to %d recipient(s); %d expiring document(s).',
            $result['sent'],
            $result['documents'],
        ));

        return self::SUCCESS;
    }
}
