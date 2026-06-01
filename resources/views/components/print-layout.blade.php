@props(['title' => 'เอกสาร'])
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4; margin: 14mm; }
        @media print { .no-print { display: none !important; } body { background: #fff; } }
        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-900">
    <div class="no-print mx-auto flex max-w-[210mm] justify-end gap-2 px-4 pt-6">
        <button onclick="window.print()" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">พิมพ์ / Save PDF</button>
        <button onclick="window.history.back()" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">กลับ</button>
    </div>
    <div class="mx-auto my-6 max-w-[210mm] bg-white p-10 text-sm shadow-sm print:my-0 print:p-0 print:shadow-none">
        {{ $slot }}
    </div>
</body>
</html>
