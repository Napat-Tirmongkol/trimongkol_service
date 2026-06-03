<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <a href="{{ route('accounting.dashboard') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.heading') }}</a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.departments') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.departments_sub') }}</p>
            </div>
            <a href="{{ route('accounting.departments.create') }}"
               class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 sm:shrink-0">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('app.accounting.department_new') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            @if ($departments->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('app.accounting.departments_empty') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('app.accounting.departments_sub') }}</p>
                    <a href="{{ route('accounting.departments.create') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                        {{ __('app.accounting.department_new') }}
                    </a>
                </div>
            @else
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                                <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.department_code') }}</th>
                                <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.department_name') }}</th>
                                <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.col_status') }}</th>
                                <th class="px-6 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($departments as $dept)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-3 font-mono text-sm text-slate-900">{{ $dept->code }}</td>
                                    <td class="px-6 py-3 text-slate-700">{{ $dept->name }}</td>
                                    <td class="px-6 py-3">
                                        @if ($dept->is_active)
                                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs text-emerald-700 ring-1 ring-emerald-200">{{ __('app.accounting.active') }}</span>
                                        @else
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500 ring-1 ring-slate-200">{{ __('app.accounting.inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-right">
                                        <div class="inline-flex items-center gap-3">
                                            <a href="{{ route('accounting.departments.edit', $dept) }}" class="text-xs text-slate-500 hover:text-slate-800">{{ __('app.common.edit') }}</a>
                                            <form method="POST" action="{{ route('accounting.departments.destroy', $dept) }}"
                                                  data-confirm="{{ __('app.accounting.department_delete_confirm') }}" data-confirm-danger="1">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-xs text-rose-500 hover:text-rose-700">{{ __('app.common.delete') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
