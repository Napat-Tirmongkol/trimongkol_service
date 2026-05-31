<x-admin-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.nav_products') }}</div>
            <h2 class="mt-0.5 text-xl font-semibold leading-tight text-gray-800">{{ __('app.admin.products.scanner.label') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.admin.products.scanner.desc') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            {{-- Primary stats --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.stat_classrooms') }}</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['classrooms'] }}</div>
                    <div class="mt-0.5 text-xs text-slate-500">+{{ $stats['classrooms_week'] }} {{ __('app.admin.products.scanner.this_week') }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.stat_assignments') }}</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['assignments'] }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.stat_submissions') }}</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['submissions'] }}</div>
                    <div class="mt-0.5 text-xs text-slate-500">+{{ $stats['submissions_week'] }} {{ __('app.admin.products.scanner.this_week') }} · {{ $stats['submissions_today'] }} {{ __('app.admin.products.scanner.today') }}</div>
                </div>
            </div>

            {{-- Top classrooms --}}
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.products.scanner.top_classrooms') }}</h3>
                    <a href="{{ route('admin.scanner.classrooms') }}" class="text-xs font-medium text-brand-700 hover:text-brand-800">{{ __('app.admin.viewAll') }} →</a>
                </div>
                @if ($topClassrooms->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.admin.classrooms_empty') }}</div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($topClassrooms as $c)
                            <li class="flex items-center justify-between px-6 py-3">
                                <div class="min-w-0">
                                    <a href="{{ route('admin.scanner.classrooms.show', $c) }}" class="text-sm font-medium text-slate-900 hover:text-brand-700">{{ $c->name }}</a>
                                    <div class="truncate text-xs text-slate-500">
                                        @if ($c->user) {{ $c->user->name }} · {{ $c->user->email }} @endif
                                    </div>
                                </div>
                                <div class="shrink-0 flex items-center gap-2 text-xs text-slate-600">
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $c->students_count }} {{ __('app.students.heading') }}</span>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $c->assignments_count }} {{ __('app.assignments.heading') }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
