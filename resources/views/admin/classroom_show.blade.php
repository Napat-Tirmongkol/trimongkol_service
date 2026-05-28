<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-3">
            <div>
                <a href="{{ route('admin.classrooms') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.admin.classrooms_heading') }}</a>
                <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">{{ $classroom->name }}</h2>
                @if ($classroom->user)
                    <p class="text-sm text-slate-500">
                        {{ __('app.admin.classroom_owner') }}:
                        <a href="{{ route('admin.users.show', $classroom->user) }}" class="font-medium text-slate-700 hover:text-brand-700">{{ $classroom->user->name }}</a>
                        <span class="text-slate-400">· {{ $classroom->user->email }}</span>
                    </p>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.students.heading') }}</div>
                    <div class="mt-2 text-2xl font-bold text-slate-900">{{ $classroom->students_count }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.assignments.heading') }}</div>
                    <div class="mt-2 text-2xl font-bold text-slate-900">{{ $classroom->assignments_count }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.col_joined') }}</div>
                    <div class="mt-2 text-base font-semibold text-slate-900">{{ $classroom->created_at->format('d M Y') }}</div>
                </div>
            </div>

            @if ($classroom->description)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.assignments.field_description') }}</div>
                    <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $classroom->description }}</p>
                </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.assignments.heading') }}</h3>
                </div>
                @if ($assignments->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.assignments.empty_short') }}</div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($assignments as $a)
                            <li class="flex items-center justify-between px-6 py-3">
                                <div>
                                    <div class="text-sm font-medium text-slate-900">{{ $a->name }}</div>
                                    <div class="mt-0.5 text-xs text-slate-500">
                                        {{ __("app.assignments.category_{$a->category}") }}
                                        · {{ __("app.assignments.mode_{$a->scoring_mode}_title") }}
                                        @if ($a->due_date) · {{ $a->due_date->format('d M Y') }} @endif
                                        · {{ $a->submissions_count }} {{ __('app.scan.submitted_label') }}
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="rounded-xl border border-rose-200 bg-rose-50/50 p-5">
                <h3 class="text-sm font-semibold text-rose-900">{{ __('app.admin.danger_zone') }}</h3>
                <p class="mt-1 text-xs text-rose-700">{{ __('app.admin.classroom_delete_hint') }}</p>
                <form method="POST" action="{{ route('admin.classrooms.destroy', $classroom) }}" class="mt-3">
                    @csrf @method('DELETE')
                    <button type="submit"
                            onclick="return confirm('{{ __('app.admin.classroom_delete_confirm', ['name' => $classroom->name]) }}')"
                            class="rounded-md border border-rose-300 bg-white px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50">
                        {{ __('app.admin.classroom_delete') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
