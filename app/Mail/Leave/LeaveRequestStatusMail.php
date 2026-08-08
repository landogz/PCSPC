<?php

namespace App\Mail\Leave;

use App\Models\LeaveRequest;
use App\Services\Administration\SystemParameterService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaveRequestStatusMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly LeaveRequest $leaveRequest,
        public readonly string $event,
    ) {}

    public function envelope(): Envelope
    {
        $brand = app(SystemParameterService::class)->current();
        $shortName = filled($brand['company_short_name'])
            ? $brand['company_short_name']
            : (string) config('app.name');

        $subject = match ($this->event) {
            'submitted' => 'Leave request submitted',
            'step' => 'Leave awaiting next approval',
            'approved' => 'Leave request approved',
            'rejected' => 'Leave request rejected',
            'cancelled' => 'Leave request cancelled',
            default => 'Leave request update',
        };

        return new Envelope(subject: $subject.' — '.$shortName);
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

        $request = $this->leaveRequest->loadMissing(['employee', 'leaveType']);

        return new Content(
            html: 'emails.leave.request-status',
            text: 'emails.leave.request-status-text',
            with: [
                'event' => $this->event,
                'employeeName' => $request->employee?->fullName() ?? 'Employee',
                'leaveType' => $request->leaveType?->name ?? 'Leave',
                'leaveCode' => $request->leaveType?->code ?? '',
                'startDate' => $request->start_date?->toDateString(),
                'endDate' => $request->end_date?->toDateString(),
                'days' => number_format((float) $request->days, 2, '.', ''),
                'reason' => $request->reason,
                'status' => $request->status,
                'approverNotes' => $request->approver_notes,
                'leaveUrl' => $appUrl.'/modules/leave',
                'appName' => $shortName,
                'companyName' => $companyName,
                'appUrl' => $appUrl,
                'logoUrl' => app(SystemParameterService::class)->logoUrl(absolute: true),
                'logoPath' => app(SystemParameterService::class)->logoFilesystemPath(),
            ],
        );
    }
}
