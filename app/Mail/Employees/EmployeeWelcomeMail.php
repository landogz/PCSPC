<?php

namespace App\Mail\Employees;

use App\Models\User;
use App\Services\Administration\SystemParameterService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeWelcomeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $temporaryPassword,
        public readonly string $employeeNumber,
    ) {}

    public function envelope(): Envelope
    {
        $brand = app(SystemParameterService::class)->current();
        $shortName = filled($brand['company_short_name'])
            ? $brand['company_short_name']
            : (string) config('app.name');

        return new Envelope(
            subject: 'Your '.$shortName.' account is ready',
        );
    }

    public function content(): Content
    {
        $brand = app(SystemParameterService::class)->current();
        $appUrl = rtrim((string) config('app.url'), '/');
        $loginUrl = $appUrl.'/login';
        $shortName = filled($brand['company_short_name'])
            ? $brand['company_short_name']
            : (string) config('app.name');
        $companyName = filled($brand['company_name'])
            ? $brand['company_name']
            : $shortName;

        return new Content(
            html: 'emails.employees.welcome',
            text: 'emails.employees.welcome-text',
            with: [
                'userName' => $this->user->name,
                'loginEmail' => $this->user->email,
                'temporaryPassword' => $this->temporaryPassword,
                'employeeNumber' => $this->employeeNumber,
                'mustChangePassword' => (bool) $this->user->must_change_password,
                'loginUrl' => $loginUrl,
                'dashboardUrl' => $appUrl.'/dashboard',
                'appName' => $shortName,
                'companyName' => $companyName,
                'appUrl' => $appUrl,
                'logoUrl' => app(SystemParameterService::class)->logoUrl(absolute: true),
                'logoPath' => app(SystemParameterService::class)->logoFilesystemPath(),
            ],
        );
    }
}
