<?php

namespace App\Mail\Auth;

use App\Models\User;
use App\Services\Administration\SystemParameterService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MfaOtpMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $otp,
        public readonly int $expiresInMinutes,
    ) {}

    public function envelope(): Envelope
    {
        $brand = app(SystemParameterService::class)->current();
        $shortName = filled($brand['company_short_name'])
            ? $brand['company_short_name']
            : (string) config('app.name');

        return new Envelope(
            subject: $shortName.' sign-in verification code',
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

        return new Content(
            html: 'emails.auth.mfa-otp',
            text: 'emails.auth.mfa-otp-text',
            with: [
                'userName' => $this->user->name,
                'otp' => $this->otp,
                'expiresInMinutes' => $this->expiresInMinutes,
                'appName' => $shortName,
                'companyName' => $companyName,
                'appUrl' => $appUrl,
                'logoUrl' => app(SystemParameterService::class)->logoUrl(absolute: true),
                'logoPath' => app(SystemParameterService::class)->logoFilesystemPath(),
            ],
        );
    }
}
