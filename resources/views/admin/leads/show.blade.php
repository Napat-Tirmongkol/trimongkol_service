<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-3">
            <div>
                <a href="{{ route('admin.leads.index') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.admin.leads.heading') }}</a>
                <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">{{ $lead->name }}</h2>
                @if ($lead->company)
                    <p class="text-sm text-slate-500">{{ $lead->company }}</p>
                @endif
            </div>
        </div>
    </x-slot>

    @php
        $badge = [
            'new' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'contacted' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'qualified' => 'bg-violet-50 text-violet-700 ring-violet-200',
            'won' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'lost' => 'bg-rose-50 text-rose-700 ring-rose-200',
        ];
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                {{-- Lead info + message --}}
                <div class="space-y-6 lg:col-span-2">
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-baseline justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.leads.section_message') }}</h3>
                                @if ($lead->source === \App\Models\Lead::SOURCE_FEEDBACK)
                                    <span class="inline-block rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-amber-700 ring-1 ring-inset ring-amber-200">{{ __('app.feedback.nav') }}</span>
                                @endif
                            </div>
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $badge[$lead->status] ?? '' }}">
                                {{ __("app.admin.leads.status_{$lead->status}") }}
                            </span>
                        </div>
                        @if ($lead->source === \App\Models\Lead::SOURCE_FEEDBACK && ! empty($lead->context['subject']))
                            <p class="mt-4 text-base font-semibold text-slate-900">{{ $lead->context['subject'] }}</p>
                            @if (! empty($lead->context['category']))
                                <p class="mt-1 text-xs text-slate-500">{{ __("app.feedback.cat_{$lead->context['category']}") }}</p>
                            @endif
                            <p class="mt-3 whitespace-pre-line text-sm text-slate-700">{{ $lead->message }}</p>
                        @else
                            <p class="mt-4 whitespace-pre-line text-sm text-slate-700">{{ $lead->message }}</p>
                        @endif

                        @if (! empty($lead->context) && $lead->source === \App\Models\Lead::SOURCE_FEEDBACK)
                            <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
                                <div class="font-semibold text-slate-700">Debug context</div>
                                <dl class="mt-2 grid gap-1 sm:grid-cols-2">
                                    @if (! empty($lead->context['page_url']))
                                        <div class="sm:col-span-2"><span class="text-slate-400">URL:</span> <code class="break-all">{{ $lead->context['page_url'] }}</code></div>
                                    @endif
                                    @if (! empty($lead->context['workspace_name']))
                                        <div><span class="text-slate-400">Workspace:</span> {{ $lead->context['workspace_name'] }} <span class="text-slate-400">#{{ $lead->context['workspace_id'] ?? '—' }}</span></div>
                                    @endif
                                    @if (! empty($lead->context['user_id']))
                                        <div><span class="text-slate-400">User ID:</span> {{ $lead->context['user_id'] }}</div>
                                    @endif
                                    @if (! empty($lead->context['locale']))
                                        <div><span class="text-slate-400">Locale:</span> {{ $lead->context['locale'] }}</div>
                                    @endif
                                </dl>
                            </div>
                        @endif

                        <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 border-t border-slate-100 pt-4 text-xs text-slate-500">
                            <div>{{ __('app.admin.leads.received') }}: {{ $lead->created_at->format('d M Y, H:i') }}</div>
                            @if ($lead->replied_at)
                                <div>{{ __('app.admin.leads.replied') }}: {{ $lead->replied_at->format('d M Y, H:i') }}</div>
                            @endif
                            @if ($lead->ip)
                                <div>IP: <code>{{ $lead->ip }}</code></div>
                            @endif
                            @if ($lead->user_agent)
                                <div class="break-all">UA: <code class="text-[10px]">{{ $lead->user_agent }}</code></div>
                            @endif
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.leads.section_contact') }}</h3>
                        <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs text-slate-500">{{ __('app.admin.leads.col_name') }}</dt>
                                <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $lead->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Email</dt>
                                <dd class="mt-0.5 text-sm"><a href="mailto:{{ $lead->email }}" class="font-medium text-brand-700 hover:text-brand-800">{{ $lead->email }}</a></dd>
                            </div>
                            @if ($lead->phone)
                                <div>
                                    <dt class="text-xs text-slate-500">{{ __('app.admin.leads.phone') }}</dt>
                                    <dd class="mt-0.5 text-sm"><a href="tel:{{ $lead->phone }}" class="font-medium text-brand-700 hover:text-brand-800">{{ $lead->phone }}</a></dd>
                                </div>
                            @endif
                            @if ($lead->company)
                                <div>
                                    <dt class="text-xs text-slate-500">{{ __('app.admin.leads.company') }}</dt>
                                    <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $lead->company }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>

                {{-- Update form --}}
                <div class="space-y-6">
                    <form method="POST" action="{{ route('admin.leads.update', $lead) }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        @csrf @method('PATCH')

                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.leads.section_manage') }}</h3>

                        <div class="mt-4 space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-600">{{ __('app.admin.leads.col_status') }}</label>
                                <select name="status" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    @foreach (\App\Models\Lead::STATUSES as $s)
                                        <option value="{{ $s }}" @selected($lead->status === $s)>{{ __("app.admin.leads.status_{$s}") }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-600">{{ __('app.admin.leads.col_assigned') }}</label>
                                <select name="assigned_to" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    <option value="">{{ __('app.admin.leads.unassigned') }}</option>
                                    @foreach ($admins as $a)
                                        <option value="{{ $a->id }}" @selected((int) $lead->assigned_to === $a->id)>{{ $a->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-600">{{ __('app.admin.leads.notes') }}</label>
                                <textarea name="internal_notes" rows="6" placeholder="{{ __('app.admin.leads.notes_placeholder') }}"
                                          class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('internal_notes', $lead->internal_notes) }}</textarea>
                            </div>
                        </div>

                        <button type="submit" class="mt-4 w-full rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                            {{ __('app.common.save') }}
                        </button>
                    </form>

                    <div class="rounded-xl border border-rose-200 bg-rose-50/50 p-5">
                        <h3 class="text-sm font-semibold text-rose-900">{{ __('app.admin.danger_zone') }}</h3>
                        <form method="POST" action="{{ route('admin.leads.destroy', $lead) }}" class="mt-3"
                              data-confirm="{{ __('app.admin.leads.delete_confirm') }}" data-confirm-danger="1">
                            @csrf @method('DELETE')
                            <button type="submit" class="rounded-md border border-rose-300 bg-white px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50">
                                {{ __('app.admin.leads.delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
