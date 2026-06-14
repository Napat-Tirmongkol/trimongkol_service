<!DOCTYPE html>
<html lang="th">
<head><meta charset="UTF-8"></head>
<body style="font-family: sans-serif; color: #1e293b; max-width: 560px; margin: 0 auto; padding: 16px;">
    <p>{{ __('app.accounting.payslip_email_greeting', ['name' => $payrollItem->employee->name]) }}</p>

    <p>{!! __('app.accounting.payslip_email_intro', [
        'period' => e($payrollItem->run->period),
        'date' => e($payrollItem->run->payment_date->format('d/m/Y')),
    ]) !!}</p>

    <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
        <tr style="background: #f1f5f9;">
            <td style="padding: 8px 12px;">{{ __('app.accounting.payroll_gross') }}</td>
            <td style="padding: 8px 12px; text-align: right;">฿{{ number_format((float) $payrollItem->gross, 2) }}</td>
        </tr>
        @if ((float) $payrollItem->overtime > 0)
            <tr><td style="padding: 6px 12px;">+ {{ __('app.accounting.payroll_overtime') }}</td>
                <td style="padding: 6px 12px; text-align: right;">฿{{ number_format((float) $payrollItem->overtime, 2) }}</td></tr>
        @endif
        @if ((float) $payrollItem->ot > 0)
            <tr><td style="padding: 6px 12px;">+ {{ __('app.accounting.payroll_ot') }}</td>
                <td style="padding: 6px 12px; text-align: right;">฿{{ number_format((float) $payrollItem->ot, 2) }}</td></tr>
        @endif
        <tr><td style="padding: 6px 12px;">− {{ __('app.accounting.payroll_ss') }}</td>
            <td style="padding: 6px 12px; text-align: right; color: #dc2626;">฿{{ number_format((float) $payrollItem->ss_employee, 2) }}</td></tr>
        @if ((float) $payrollItem->wht > 0)
            <tr><td style="padding: 6px 12px;">− {{ __('app.accounting.payroll_wht') }}</td>
                <td style="padding: 6px 12px; text-align: right; color: #dc2626;">฿{{ number_format((float) $payrollItem->wht, 2) }}</td></tr>
        @endif
        @if ((float) $payrollItem->other_deductions > 0)
            <tr><td style="padding: 6px 12px;">− {{ __('app.accounting.payroll_other') }}</td>
                <td style="padding: 6px 12px; text-align: right; color: #dc2626;">฿{{ number_format((float) $payrollItem->other_deductions, 2) }}</td></tr>
        @endif
        <tr style="background: #dbeafe; font-weight: 700;">
            <td style="padding: 10px 12px;">{{ __('app.accounting.payroll_net') }}</td>
            <td style="padding: 10px 12px; text-align: right;">฿{{ number_format((float) $payrollItem->net, 2) }}</td>
        </tr>
    </table>

    <p style="font-size: 13px; color: #64748b;">{{ __('app.accounting.payslip_email_attach_note') }}</p>
    <p style="font-size: 13px; color: #64748b;">{{ $workspace->name ?? '' }}</p>
</body>
</html>
