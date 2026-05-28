<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between">
            <div>
                <a href="{{ route('dashboard') }}" class="text-xs text-slate-500 hover:text-slate-700">
                    ← {{ __('app.classrooms.heading') }}
                </a>
                <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">
                    {{ $classroom->name }}
                    @if ($classroom->grade_level)
                        <span class="ml-2 rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-700 align-middle">
                            {{ $classroom->grade_level }}
                        </span>
                    @endif
                </h2>
                @if ($classroom->description)
                    <p class="mt-1 text-sm text-slate-600">{{ $classroom->description }}</p>
                @endif
            </div>
            <a href="{{ route('classrooms.edit', $classroom) }}"
               class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                {{ __('app.common.edit') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <h3 class="text-base font-semibold text-slate-900">
                        {{ __('app.students.heading') }}
                        <span class="ml-1 text-sm font-normal text-slate-500">({{ $classroom->students->count() }})</span>
                    </h3>
                    <a href="{{ route('classrooms.students.create', $classroom) }}"
                       class="rounded-md bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700">
                        + {{ __('app.students.add') }}
                    </a>
                </div>

                @if ($classroom->students->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <p class="text-sm text-slate-600">{{ __('app.students.empty') }}</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr class="text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                                    <th class="px-6 py-3">{{ __('app.students.col_number') }}</th>
                                    <th class="px-6 py-3">{{ __('app.students.col_name') }}</th>
                                    <th class="px-6 py-3">{{ __('app.students.col_code') }}</th>
                                    <th class="px-6 py-3 text-right">{{ __('app.students.col_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($classroom->students as $student)
                                    <tr class="text-sm">
                                        <td class="px-6 py-3 text-slate-600">{{ $student->number ?: '—' }}</td>
                                        <td class="px-6 py-3 font-medium text-slate-900">{{ $student->name }}</td>
                                        <td class="px-6 py-3 font-mono text-xs text-slate-600">{{ $student->code }}</td>
                                        <td class="px-6 py-3 text-right">
                                            <a href="{{ route('classrooms.students.edit', [$classroom, $student]) }}"
                                               class="text-brand-700 hover:text-brand-800">{{ __('app.common.edit') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
