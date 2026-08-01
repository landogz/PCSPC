<?php

namespace App\Mail\Documents;

use App\Models\User;
use App\Services\Administration\SystemParameterService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DocumentExpiryDigestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  Collection<int, \App\Models\EmployeeDocument>  $expiring
     */
    public function __construct(
        public readonly User $user,
        public readonly Collection $expiring,
        public readonly int $expiredCount,
        public readonly int $withinDays,
    ) {}

    public function envelope(): Envelope
    {
        $brand = app(SystemParameterService::class)->current();
        $shortName = filled($brand['company_short_name'])
            ? $brand['company_short_name']
            : (string) config('app.name');

        return new Envelope(
            subject: $shortName.' — documents expiring soon',
        );
    }

    public function content(): Content
    {
        $brand = app(SystemParameterService::class)->current();
        $appUrl = rtrim((string) config('app.url'), '/');
        $shortName = filled($brand['company_short_name'])
            ? $brand['company_short_name']
            : (string) config('app.name');
        $companyName = filled($brand['company_name'])
            ? $brand['company_name']
            : $shortName;

        $rows = $this->expiring->take(25)->map(static function ($document): array {
            return [
                'title' => $document->title,
                'employee' => trim(($document->employee?->first_name ?? '').' '.($document->employee?->last_name ?? '')),
                'expires_at' => $document->expires_at?->toDateString(),
                'category' => $document->category,
            ];
        })->values()->all();

        return new Content(
            html: 'emails.documents.expiry-digest',
            text: 'emails.documents.expiry-digest-text',
            with: [
                'userName' => $this->user->name,
                'withinDays' => $this->withinDays,
                'expiringCount' => $this->expiring->count(),
                'expiredCount' => $this->expiredCount,
                'rows' => $rows,
                'documentsUrl' => $appUrl.'/modules/documents?expiry=expiring',
                'appName' => $shortName,
                'companyName' => $companyName,
                'appUrl' => $appUrl,
                'logoUrl' => app(SystemParameterService::class)->logoUrl(absolute: true),
                'logoPath' => app(SystemParameterService::class)->logoFilesystemPath(),
            ],
        );
    }
}
