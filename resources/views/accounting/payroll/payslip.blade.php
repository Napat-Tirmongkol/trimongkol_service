<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>{{ __('app.accounting.payslip') }} {{ $payrollItem->employee->name }}</title>
    <style>
        body { font-family: 'Sarabun', sans-serif; max-width: 720px; margin: 24px auto; color: #1e293b; padding: 0 16px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .muted { color: #64748b; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 8px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
        th { text-align: left; background: #f8fafc; }
        .text-right { text-align: right; }
        .total-row { font-weight: 700; background: #f1f5f9; }
        .net-row { background: #dbeafe; }
        @media print { body { margin: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div style="display: flex; justify-content: space-between; align-items: start;">
        <div>
            <h1>{{ __('app.accounting.payslip') }}</h1>
            <div class="muted">{{ $workspace->name ?? '' }}</div>
        </div>
        <div style="text-align: right;">
            <div><strong>{{ $payrollItem->run->no }}</strong></div>
            <div class="muted">{{ __('app.accounting.payroll_period') }}: {{ $payrollItem->run->period }}</div>
            <div class="muted">{{ __('app.accounting.payment_date') }}: {{ $payrollItem->run->payment_date->format('d/m/Y') }}</div>
        </div>
    </div>

    <hr style="margin: 16px 0; border: none; border-top: 1px solid #e2e8f0;">

    <div>
        <strong>{{ $payrollItem->employee->name }}</strong> ({{ $payrollItem->employee->code }})<br>
        @if ($payrollItem->employee->position)
            <span class="muted">{{ $payrollItem->employee->position }}</span><br>
        @endif
        @if ($payrollItem->employee->tax_id)
            <span class="muted">{{ __('app.accounting.col_tax_id') }}: {{ $payrollItem->employee->tax_id }}</span><br>
        @endif
        @if ($payrollItem->employee->social_security_id)
            <span class="muted">{{ __('app.accounting.employee_ss_id') }}: {{ $payrollItem->employee->social_security_id }}</span>
        @endif
    </div>

    <table>
        <thead>
            <tr><th>{{ __('app.accounting.line_desc') }}</th><th class="text-right">{{ __('app.accounting.col_amount') }}</th></tr>
        </thead>
        <tbody>
            <tr><td>{{ __('app.accounting.payroll_gross') }}</td><td class="text-right">฿{{ number_format((float) $payrollItem->gross, 2) }}</td></tr>
            <tr><td style="padding-left: 24px;">− {{ __('app.accounting.payroll_ss') }}</td><td class="text-right">฿{{ number_format((float) $payrollItem->ss_employee, 2) }}</td></tr>
            <tr><td style="padding-left: 24px;">− {{ __('app.accounting.payroll_wht') }}</td><td class="text-right">฿{{ number_format((float) $payrollItem->wht, 2) }}</td></tr>
            @if ((float) $payrollItem->other_deductions > 0)
                <tr><td style="padding-left: 24px;">− {{ __('app.accounting.payroll_other') }}</td><td class="text-right">฿{{ number_format((float) $payrollItem->other_deductions, 2) }}</td></tr>
            @endif
            <tr class="net-row"><td><strong>{{ __('app.accounting.payroll_net') }}</strong></td><td class="text-right"><strong>฿{{ number_format((float) $payrollItem->net, 2) }}</strong></td></tr>
        </tbody>
    </table>

    @if ($payrollItem->employee->bank_account)
        <p class="muted" style="margin-top: 24px;">
            {{ __('app.accounting.employee_bank') }}: {{ $payrollItem->employee->bank_account }}
        </p>
    @endif

    <div class="no-print" style="margin-top: 32px; text-align: center;">
        <button onclick="window.print()" style="padding: 8px 20px; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer;">
            {{ __('app.accounting.print') }}
        </button>
    </div>
</body>
</html>
