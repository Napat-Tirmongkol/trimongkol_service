<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('app.students.print_qr_heading') }} — {{ $student->name }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .qr-card { box-shadow: none !important; border: 1px solid #cbd5e1 !important; }
        }
        @page { margin: 16mm; }
    </style>
</head>
<body class="bg-slate-100 text-slate-900">
    <header class="no-print sticky top-0 z-10 border-b border-slate-200 bg-white shadow-sm">
        <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-3">
            <a href="{{ route('classrooms.students.show', [$classroom, $student]) }}"
               class="text-sm text-slate-600 hover:text-slate-900">
                ← {{ $student->name }}
            </a>
            <h1 class="text-sm font-semibold text-slate-900">{{ __('app.students.print_qr_heading') }}</h1>
            <button onclick="window.print()"
                    class="rounded-md bg-brand-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-brand-700">
                {{ __('app.students.print_button') }}
            </button>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-8">
        <div class="no-print mb-4 rounded-lg bg-white p-4 text-sm text-slate-600 shadow-sm">
            {{ __('app.students.print_qr_intro') }}
        </div>

        <div class="qr-card mx-auto flex max-w-md flex-col items-center rounded-2xl border border-slate-300 bg-white p-8 shadow-sm">
            <div class="text-xs uppercase tracking-wider text-slate-500">{{ $classroom->name }}</div>
            <canvas data-qr="{{ $student->code }}" class="mt-4 h-64 w-64"></canvas>
            <div class="mt-5 w-full border-t border-slate-200 pt-4 text-center">
                <div class="text-xl font-bold text-slate-900">
                    @if ($student->number) <span class="text-slate-400">#{{ $student->number }}</span> @endif
                    {{ $student->name }}
                </div>
                <div class="mt-1 font-mono text-sm text-slate-500">{{ $student->code }}</div>
            </div>
        </div>
    </main>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const draw = () => {
                if (!window.QRCode) { setTimeout(draw, 100); return; }
                document.querySelectorAll('canvas[data-qr]').forEach((c) => {
                    const code = c.getAttribute('data-qr');
                    window.QRCode.toCanvas(c, code, {
                        width: 400,
                        margin: 1,
                        color: { dark: '#0f172a', light: '#ffffff' },
                    }, (err) => { if (err) console.error(err); });
                });
            };
            draw();
        });
    </script>
</body>
</html>
