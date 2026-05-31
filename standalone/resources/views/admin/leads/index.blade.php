<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('app.admin.leads.heading') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @php
                // Tailwind JIT can only see literal class strings, so keep them explicit here.
                $pillActive = [
                    'new' => 'bg-amber-600 text-white ring-amber-600',
                    'contacted' => 'bg-sky-600 text-white ring-sky-600',
                    'qualified' => 'bg-violet-600 text-white ring-violet-600',
                    'won' => 'bg-emerald-600 text-white ring-emerald-600',
                    'lost' => 'bg-rose-600 text-white ring-rose-600',
                ];
                $pillIdle = [
                    'new' => 'bg-amber-50 text-amber-700 ring-amber-200 hover:bg-amber-100',
                    'contacted' => 'bg-sky-50 text-sky-700 ring-sky-200 hover:bg-sky-100',
                    'qualified' => 'bg-violet-50 text-violet-700 ring-violet-200 hover:bg-violet-100',
                    'won' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 hover:bg-emerald-100',
                    'lost' => 'bg-rose-50 text-rose-700 ring-rose-200 hover:bg-rose-100',
                ];
                $badge = [
                    'new' => 'bg-amber-50 text-amber-700 ring-amber-200',
                    'contacted' => 'bg-sky-50 text-sky-700 ring-sky-200',
                    'qualified' => 'bg-violet-50 text-violet-700 ring-violet-200',
                    'won' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                    'lost' => 'bg-rose-50 text-rose-700 ring-rose-200',
                ];
            @endphp

            {{-- Status pills (filter) --}}
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.leads.index') }}"
                   class="rounded-full px-3 py-1.5 text-sm font-medium {{ ! $status ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                    {{ __('app.admin.filter_all') }} ({{ array_sum($counts) }})
                </a>
                @foreach (\App\Models\Lead::STATUSES as $s)
                    <a href="{{ route('admin.leads.index', ['status' => $s]) }}"
                       class="rounded-full px-3 py-1.5 text-sm font-medium ring-1 {{ $status === $s ? $pillActive[$s] : $pillIdle[$s] }}">
                        {{ __("app.admin.leads.status_{$s}") }} ({{ $counts[$s] ?? 0 }})
                    </a>
                @endforeach
            </div>

            <form method="GET" class="flex gap-2">
                @if ($status) <input type="hidden" name="status" value="{{ $status }}"> @endif
                <input type="text" name="q" value="{{ $q }}"
                       placeholder="{{ __('app.admin.leads.search') }}"
                       class="block w-full max-w-md rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    {{ __('app.admin.search') }}
                </button>
                @if ($q !== '')
                    <a href="{{ route('admin.leads.index', ['status' => $status]) }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">{{ __('app.admin.clear') }}</a>
                @endif
            </form>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-6 py-3">{{ __('app.admin.leads.col_name') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.leads.col_contact') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.leads.col_status') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.leads.col_assigned') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.leads.col_received') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-sm">
                            @forelse ($leads as $lead)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-6 py-3">
                                        <a href="{{ route('admin.leads.show', $lead) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $lead->name }}</a>
                                        @if ($lead->company)
                                            <div class="text-xs text-slate-500">{{ $lead->company }}</div>
                                        @endif
                                        @if ($lead->source === \App\Models\Lead::SOURCE_FEEDBACK)
                                            <span class="mt-1 inline-block rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-amber-700 ring-1 ring-inset ring-amber-200">{{ __('app.feedback.nav') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-slate-600">
                                        <div>{{ $lead->email }}</div>
                                        @if ($lead->phone)
                                            <div class="text-xs text-slate-500">{{ $lead->phone }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $badge[$lead->status] ?? '' }}">
                                            {{ __("app.admin.leads.status_{$lead->status}") }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-xs text-slate-600">
                                        {{ $lead->assignee?->name ?: '—' }}
                                    </td>
                                    <td class="px-6 py-3 text-xs text-slate-500" title="{{ $lead->created_at }}">
                                        {{ $lead->created_at->diffForHumans() }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">{{ __('app.admin.leads.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $leads->links() }}</div>
        </div>
    </div>
</x-admin-layout>
