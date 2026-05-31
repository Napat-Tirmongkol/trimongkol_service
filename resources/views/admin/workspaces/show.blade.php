<x-admin-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('admin.workspaces.index') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.admin.workspaces.heading') }}</a>
            <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">{{ $workspace->name }}</h2>
            <p class="text-xs text-slate-400">/{{ $workspace->slug }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Stats --}}
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.workspaces.col_members') }}</div>
                    <div class="mt-2 text-2xl font-bold text-slate-900">{{ $workspace->members_count }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.workspaces.col_classrooms') }}</div>
                    <div class="mt-2 text-2xl font-bold text-slate-900">{{ $workspace->classrooms_count }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.col_joined') }}</div>
                    <div class="mt-2 text-base font-semibold text-slate-900">{{ $workspace->created_at->format('d M Y') }}</div>
                </div>
            </div>

            {{-- Plan / Subscription (admin manage) --}}
            @php
                $sub = $workspace->subscription;
                $effectivePlanKey = $sub?->effectivePlanKey() ?? 'free';
                $effectivePlan = $plans[$effectivePlanKey] ?? null;
            @endphp
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.workspaces.plan_heading') }}</h3>
                <div class="mt-3 flex flex-wrap items-baseline gap-3 text-sm">
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 font-semibold text-slate-700">{{ $effectivePlan?->name ?: 'Free' }}</span>
                    @if ($sub?->isOnTrial())
                        <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-200">
                            {{ __('app.plans.trial_remaining', ['days' => $sub->trialDaysLeft()]) }}
                        </span>
                    @endif
                    @if ($sub)
                        <span class="text-xs text-slate-500">status: <code>{{ $sub->status }}</code> · plan: <code>{{ $sub->plan_key }}</code></span>
                    @endif
                </div>

                <form method="POST" action="{{ route('admin.workspaces.update-plan', $workspace) }}" class="mt-5 grid gap-3 sm:grid-cols-3 border-t border-slate-100 pt-5">
                    @csrf @method('PATCH')
                    <div>
                        <label for="plan_key" class="block text-xs font-medium text-slate-600">{{ __('app.admin.workspaces.plan_key') }}</label>
                        <select id="plan_key" name="plan_key" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @foreach ($plans as $key => $p)
                                <option value="{{ $key }}" @selected($sub?->plan_key === $key)>{{ $p->name }} ({{ $key }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-xs font-medium text-slate-600">{{ __('app.admin.workspaces.plan_status') }}</label>
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="active" @selected($sub?->status === 'active')>active</option>
                            <option value="trial" @selected($sub?->status === 'trial')>trial</option>
                        </select>
                    </div>
                    <div>
                        <label for="trial_days" class="block text-xs font-medium text-slate-600">{{ __('app.admin.workspaces.plan_trial_days') }}</label>
                        <input id="trial_days" name="trial_days" type="number" min="1" max="365" value="14"
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <p class="mt-1 text-xs text-slate-500">{{ __('app.admin.workspaces.plan_trial_days_hint') }}</p>
                    </div>
                    <div class="sm:col-span-3">
                        <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                            {{ __('app.admin.workspaces.plan_save') }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- Queue product plan (billed separately) --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.workspaces.queue_plan_heading') }}</h3>
                <div class="mt-3 flex flex-wrap items-baseline gap-3 text-sm">
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 font-semibold text-slate-700">{{ \App\Services\QueuePlan::name($workspace) }}</span>
                    <span class="text-xs text-slate-500">key: <code>{{ $workspace->queue_plan ?: 'free' }}</code></span>
                </div>
                <form method="POST" action="{{ route('admin.workspaces.update-queue-plan', $workspace) }}" class="mt-5 flex flex-wrap items-end gap-3 border-t border-slate-100 pt-5">
                    @csrf @method('PATCH')
                    <div>
                        <label for="queue_plan" class="block text-xs font-medium text-slate-600">{{ __('app.admin.workspaces.queue_plan_label') }}</label>
                        <select id="queue_plan" name="queue_plan" class="mt-1 block w-56 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @foreach (config('queue-plans') as $key => $p)
                                <option value="{{ $key }}" @selected(($workspace->queue_plan ?: 'free') === $key)>{{ $p['name'] }} ({{ $key }})</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('app.admin.workspaces.plan_save') }}</button>
                </form>
            </div>

            {{-- Accounting product plan (billed separately) --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.workspaces.accounting_plan_heading') }}</h3>
                <div class="mt-3 flex flex-wrap items-baseline gap-3 text-sm">
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 font-semibold text-slate-700">{{ \App\Services\AccountingPlan::name($workspace) }}</span>
                    <span class="text-xs text-slate-500">key: <code>{{ $workspace->accounting_plan ?: 'free' }}</code></span>
                    @if (\App\Services\AccountingPlan::expiresAt($workspace))
                        <span class="text-xs {{ \App\Services\AccountingPlan::isExpired($workspace) ? 'text-rose-600' : 'text-slate-500' }}">
                            {{ \App\Services\AccountingPlan::isExpired($workspace) ? __('app.admin.workspaces.plan_expired') : __('app.admin.workspaces.plan_until') }}
                            {{ \App\Services\AccountingPlan::expiresAt($workspace)->format('d/m/Y') }}
                        </span>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.workspaces.update-accounting-plan', $workspace) }}" class="mt-5 flex flex-wrap items-end gap-3 border-t border-slate-100 pt-5">
                    @csrf @method('PATCH')
                    <div>
                        <label for="accounting_plan" class="block text-xs font-medium text-slate-600">{{ __('app.admin.workspaces.accounting_plan_label') }}</label>
                        <select id="accounting_plan" name="accounting_plan" class="mt-1 block w-56 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @foreach (config('accounting-plans') as $key => $p)
                                <option value="{{ $key }}" @selected(($workspace->accounting_plan ?: 'free') === $key)>{{ $p['name'] }} ({{ $key }}) — ฿{{ number_format($p['price']) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="months" class="block text-xs font-medium text-slate-600">{{ __('app.admin.workspaces.plan_months') }}</label>
                        <input id="months" name="months" type="number" min="1" max="36" value="1" class="mt-1 block w-24 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    </div>
                    <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('app.admin.workspaces.plan_save') }}</button>
                </form>
            </div>

            {{-- Members --}}
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.workspaces.section_members') }} ({{ $memberships->count() }})</h3>
                </div>
                <ul class="divide-y divide-slate-100">
                    @foreach ($memberships as $m)
                        <li class="flex items-center justify-between gap-3 px-6 py-3">
                            <div class="min-w-0">
                                <a href="{{ route('admin.users.show', $m->user) }}" class="text-sm font-medium text-slate-900 hover:text-brand-700">{{ $m->user->name }}</a>
                                <div class="text-xs text-slate-500">{{ $m->user->email }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($m->role === 'owner')
                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-200">{{ __('app.workspaces.role_owner') }}</span>
                                @elseif ($m->role === 'admin')
                                    <span class="rounded-full bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700 ring-1 ring-inset ring-sky-200">{{ __('app.workspaces.role_admin') }}</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ __('app.workspaces.role_member') }}</span>
                                @endif
                                @if (! $m->user->is_active)
                                    <span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700">{{ __('app.admin.status_suspended') }}</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Pending invitations --}}
            @if ($pendingInvitations->isNotEmpty())
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-3">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.workspaces.pending_invites_heading') }} ({{ $pendingInvitations->count() }})</h3>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($pendingInvitations as $inv)
                            <li class="flex items-center justify-between px-6 py-3">
                                <div>
                                    <div class="text-sm text-slate-700">{{ $inv->email }}</div>
                                    <div class="text-xs text-slate-500">{{ __("app.workspaces.role_{$inv->role}") }} · {{ __('app.workspaces.expires_in', ['at' => $inv->expires_at->diffForHumans()]) }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Classrooms --}}
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.classrooms.heading') }} ({{ $classrooms->count() }})</h3>
                </div>
                @if ($classrooms->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.classrooms.empty') ?? __('app.admin.classrooms_empty') }}</div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($classrooms as $c)
                            <li class="flex items-center justify-between px-6 py-3">
                                <div class="min-w-0">
                                    <a href="{{ route('admin.scanner.classrooms.show', $c) }}" class="text-sm font-medium text-slate-900 hover:text-brand-700">{{ $c->name }}</a>
                                    <div class="text-xs text-slate-500">
                                        {{ __('app.admin.workspaces.created_by') }}: {{ $c->user?->name ?: '—' }}
                                        · {{ $c->students_count }} {{ __('app.students.heading') }}
                                        · {{ $c->assignments_count }} {{ __('app.assignments.heading') }}
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Danger zone --}}
            <div class="rounded-xl border border-rose-200 bg-rose-50/40 p-6">
                <h3 class="text-sm font-semibold text-rose-900">{{ __('app.admin.danger_zone') }}</h3>
                <p class="mt-1 text-xs text-rose-700">{{ __('app.admin.workspaces.delete_hint') }}</p>
                <form method="POST" action="{{ route('admin.workspaces.destroy', $workspace) }}" class="mt-3"
                      data-confirm="{{ __('app.admin.workspaces.delete_confirm', ['name' => $workspace->name]) }}" data-confirm-danger="1">
                    @csrf @method('DELETE')
                    <button type="submit" class="rounded-md border border-rose-300 bg-white px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50">
                        {{ __('app.admin.workspaces.delete') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
