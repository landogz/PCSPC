<?php

namespace App\Mail\Overtime;

use App\Models\OvertimeRequest;
use App\Services\Administration\SystemParameterService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OvertimeRequestStatusMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly OvertimeRequest $overtimeRequest,
        public readonly string $event,
    ) {}

    public function envelope(): Envelope
    {
        $brand = app(SystemParameterService::class)->current();
        $shortName = filled($brand['company_short_name'])
            ? $brand['company_short_name']
            : (string) config('app.name');

        $subject = match ($this->event) {
            'submitted' => 'Overtime request submitted',
            'step' => 'Overtime awaiting next approval',
            'approved' => 'Overtime request approved',
            'rejected' => 'Overtime request rejected',
            'cancelled' => 'Overtime request cancelled',
            default => 'Overtime request update',
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

        $request = $this->overtimeRequest->loadMissing(['employee', 'workflowInstance.definition.steps']);
        $step = $request->workflowInstance?->currentStep();

        return new Content(
            html: 'emails.overtime.request-status',
            text: 'emails.overtime.request-status-text',
            with: [
                'event' => $this->event,
                'employeeName' => $request->employee?->fullName() ?? 'Employee',
                'kindLabel' => $request->kind === 'ot_meal' ? 'OT Meal' : 'OT',
                'workDate' => $request->work_date?->toDateString(),
                'hours' => number_format((float) $request->hours, 2, '.', ''),
                'reason' => $request->reason,
                'mealNotes' => $request->meal_notes,
                'status' => $request->status,
                'stepLabel' => $step?->label,
                'overtimeUrl' => $appUrl.'/modules/overtime',
                'appName' => $shortName,
                'companyName' => $companyName,
                'appUrl' => $appUrl,
                'logoUrl' => app(SystemParameterService::class)->logoUrl(absolute: true),
            ],
        );
    }
}
