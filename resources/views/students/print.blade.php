<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('app.students.print_heading') }} — {{ $classroom->name }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .qr-card { break-inside: avoid; page-break-inside: avoid; }
        }
        .qr-card { break-inside: avoid; }
        @page { margin: 12mm; }
    </style>
</head>
<body class="bg-slate-100 text-slate-900">
    <header class="no-print sticky top-0 z-10 border-b border-slate-200 bg-white shadow-sm">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
            <a href="{{ route('classrooms.show', $classroom) }}" class="text-sm text-slate-600 hover:text-slate-900">
                ← {{ $classroom->name }}
            </a>
            <h1 class="text-sm font-semibold text-slate-900">{{ __('app.students.print_heading') }}</h1>
            <button onclick="window.print()"
                    class="rounded-md bg-brand-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-brand-700">
                {{ __('app.students.print_button') }}
            </button>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-6">
        <div class="no-print mb-4 rounded-lg bg-white p-4 text-sm text-slate-600 shadow-sm">
            {{ __('app.students.print_intro', ['count' => $students->count()]) }}
        </div>

        @if ($students->isEmpty())
            <div class="rounded-lg border-2 border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-600">
                {{ __('app.students.empty') }}
            </div>
        @else
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                @foreach ($students as $student)
                    <div class="qr-card flex flex-col items-center rounded-lg border border-slate-300 bg-white p-3">
                        <canvas data-qr="{{ $student->code }}" class="h-32 w-32"></canvas>
                        <div class="mt-2 w-full text-center">
                            <div class="truncate text-sm font-semibold text-slate-900">
                                @if ($student->number) <span class="text-slate-500">#{{ $student->number }}</span> @endif
                                {{ $student->name }}
                            </div>
                            <div class="mt-0.5 font-mono text-xs text-slate-500">{{ $student->code }}</div>
                            <div class="mt-0.5 text-[10px] text-slate-400">{{ $classroom->name }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </main>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const draw = () => {
                if (!window.QRCode) { setTimeout(draw, 100); return; }
                document.querySelectorAll('canvas[data-qr]').forEach((c) => {
                    const code = c.getAttribute('data-qr');
                    window.QRCode.toCanvas(c, code, {
                        width: 200,
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
