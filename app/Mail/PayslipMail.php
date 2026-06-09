<?php

namespace App\Mail;

use App\Models\Accounting\PayrollItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayslipMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PayrollItem $payrollItem)
    {
        $this->payrollItem->loadMissing('employee', 'run', 'workspace');
    }

    public function envelope(): Envelope
    {
        $period = $this->payrollItem->run->period;

        return new Envelope(
            subject: __('app.accounting.payslip_email_subject', ['period' => $period]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payslip',
            with: [
                'payrollItem' => $this->payrollItem,
                'workspace' => $this->payrollItem->workspace,
            ],
        );
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('accounting.payroll.payslip', [
            'payrollItem' => $this->payrollItem,
            'workspace' => $this->payrollItem->workspace,
        ])->setPaper('a4');

        $filename = sprintf(
            'payslip-%s-%s.pdf',
            $this->payrollItem->run->period,
            preg_replace('/[^a-z0-9]+/i', '-', $this->payrollItem->employee->code ?: 'employee')
        );

        return [
            Attachment::fromData(fn () => $pdf->output(), $filename)
                ->withMime('application/pdf'),
        ];
    }
}
