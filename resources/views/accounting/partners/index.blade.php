<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('accounting.dashboard') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.heading') }}</a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.partners') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.partners_sub') }}</p>
            </div>
            @if ($workspace)
                <a href="{{ route('accounting.partners.create') }}"
                   class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 sm:shrink-0">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ __('app.accounting.partner_new') }}
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if ($workspace)
                <form method="GET" action="{{ route('accounting.partners.index') }}" class="flex flex-wrap items-end gap-2">
                    <div class="flex-1 min-w-[200px]">
                        <label for="q" class="block text-xs font-medium text-slate-500">{{ __('app.accounting.search') }}</label>
                        <input id="q" name="q" type="search" value="{{ $q }}"
                               placeholder="{{ __('app.accounting.search_partner_placeholder') }}"
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    </div>
                    <div>
                        <label for="type" class="block text-xs font-medium text-slate-500">{{ __('app.accounting.partner_type') }}</label>
                        <select id="type" name="type" class="mt-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">{{ __('app.accounting.partner_type_all') }}</option>
                            <option value="customer" @selected($type === 'customer')>{{ __('app.accounting.partner_is_customer') }}</option>
                            <option value="vendor" @selected($type === 'vendor')>{{ __('app.accounting.partner_is_vendor') }}</option>
                        </select>
                    </div>
                    <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('app.accounting.apply') }}</button>
                    @if ($q !== '' || $type !== '')
                        <a href="{{ route('accounting.partners.index') }}" class="text-sm text-slate-500 hover:text-slate-800">{{ __('app.accounting.clear') }}</a>
                    @endif
                </form>
            @endif

            @if (! $workspace)
                <div class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">{{ __('app.workspaces.no_workspace') }}</div>
            @elseif ($partners->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                    @if ($q !== '' || $type !== '')
                        <h3 class="text-lg font-semibold text-slate-900">{{ __('app.accounting.no_match') }}</h3>
                        <a href="{{ route('accounting.partners.index') }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:text-brand-800">
                            {{ __('app.accounting.clear') }} →
                        </a>
                    @else
                        <h3 class="text-lg font-semibold text-slate-900">{{ __('app.accounting.partners_empty') }}</h3>
                        <a href="{{ route('accounting.partners.create') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                            {{ __('app.accounting.partner_new') }}
                        </a>
                    @endif
                </div>
            @else
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                                <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.partner_name') }}</th>
                                <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.partner_tax_id') }}</th>
                                <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.partner_type') }}</th>
                                <th class="px-6 py-2.5 text-right font-medium">{{ __('app.accounting.partner_credit_days') }}</th>
                                <th class="px-6 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($partners as $partner)
                                <tr>
                                    <td class="px-6 py-3">
                                        <div class="font-medium text-slate-900">{{ $partner->name }}</div>
                                        @if ($partner->code)<div class="text-xs text-slate-400">{{ $partner->code }}</div>@endif
                                    </td>
                                    <td class="px-6 py-3 font-mono text-xs text-slate-600">{{ $partner->tax_id ?: '—' }}</td>
                                    <td class="px-6 py-3">
                                        <div class="flex flex-wrap gap-1">
                                            @if ($partner->is_customer)<span class="rounded-full bg-sky-50 px-2 py-0.5 text-xs text-sky-700 ring-1 ring-sky-200">{{ __('app.accounting.partner_is_customer') }}</span>@endif
                                            @if ($partner->is_vendor)<span class="rounded-full bg-violet-50 px-2 py-0.5 text-xs text-violet-700 ring-1 ring-violet-200">{{ __('app.accounting.partner_is_vendor') }}</span>@endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 text-right tabular-nums text-slate-600">{{ $partner->credit_days }}</td>
                                    <td class="px-6 py-3 text-right">
                                        <div class="inline-flex items-center gap-3">
                                            <a href="{{ route('accounting.partners.edit', $partner) }}" class="text-xs text-slate-500 hover:text-slate-800">{{ __('app.common.edit') }}</a>
                                            <form method="POST" action="{{ route('accounting.partners.destroy', $partner) }}"
                                                  data-confirm="{{ __('app.accounting.partner_delete_confirm') }}" data-confirm-danger="1">
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
                <div class="mt-4">{{ $partners->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
