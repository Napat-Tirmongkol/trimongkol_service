<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('workspaces.index') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.workspaces.heading') }}</a>
            <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">{{ $workspace->name }}</h2>
            <p class="text-sm text-slate-500">{{ __('app.workspaces.settings_subtitle') }}</p>
        </div>
    </x-slot>

    @php
        $canManage = in_array($role, ['owner', 'admin'], true);
        $isOwner = $role === 'owner';
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Plan summary --}}
            @php
                $sub = $workspace->subscription;
                $plan = $workspace->currentPlan();
            @endphp
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.plans.current_plan_label') }}</div>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span class="text-lg font-semibold text-slate-900">{{ $plan->name }}</span>
                            @if (\App\Services\Billing::freeMode())
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                    {{ __('app.plans.free_launch_badge') }}
                                </span>
                            @elseif ($sub?->isOnTrial())
                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-200">
                                    {{ __('app.plans.trial_remaining', ['days' => $sub->trialDaysLeft()]) }}
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ $plan->tagline }}</p>
                    </div>
                    <a href="{{ route('plans.index') }}" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        {{ __('app.plans.view_all_plans') }}
                    </a>
                </div>
            </div>

            {{-- Rename --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('app.workspaces.section_general') }}</h3>
                <form method="POST" action="{{ route('workspaces.update', $workspace) }}" class="mt-4 space-y-3">
                    @csrf @method('PATCH')
                    <div>
                        <label for="name" class="block text-xs font-medium text-slate-600">{{ __('app.workspaces.field_name') }}</label>
                        <input id="name" name="name" type="text" required maxlength="80" @disabled(! $canManage)
                               value="{{ old('name', $workspace->name) }}"
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-50 disabled:text-slate-500">
                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    @if ($canManage)
                        <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                            {{ __('app.common.save') }}
                        </button>
                    @endif
                </form>
            </div>

            {{-- Members --}}
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.workspaces.section_members') }} ({{ $members->count() }})</h3>
                </div>

                <ul class="divide-y divide-slate-100">
                    @foreach ($members as $m)
                        <li class="flex items-center justify-between gap-3 px-6 py-3">
                            <div>
                                <div class="text-sm font-medium text-slate-900">{{ $m->user->name }}</div>
                                <div class="text-xs text-slate-500">{{ $m->user->email }} · {{ __('app.workspaces.joined_at', ['at' => $m->joined_at?->format('d M Y')]) }}</div>
                            </div>
                            <div class="flex items-center gap-3">
                                @if ($m->role === 'owner')
                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-200">{{ __('app.workspaces.role_owner') }}</span>
                                @elseif ($m->role === 'admin')
                                    <span class="rounded-full bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700 ring-1 ring-inset ring-sky-200">{{ __('app.workspaces.role_admin') }}</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ __('app.workspaces.role_member') }}</span>
                                @endif

                                @if ($canManage && $m->role !== 'owner' && $m->user->id !== auth()->id())
                                    <form method="POST" action="{{ route('workspaces.members.remove', [$workspace, $m->user]) }}"
                                          data-confirm="{{ __('app.workspaces.remove_confirm', ['name' => $m->user->name]) }}" data-confirm-danger="1">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-700">{{ __('app.workspaces.remove_member') }}</button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- Pending invitations (email invites awaiting accept) --}}
                @if ($canManage && $pendingInvitations->isNotEmpty())
                    <div class="border-t border-slate-200 bg-slate-50 px-6 py-4">
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                            {{ __('app.workspaces.pending_invites_heading') }} ({{ $pendingInvitations->count() }})
                        </h4>
                        <ul class="mt-2 space-y-2">
                            @foreach ($pendingInvitations as $inv)
                                @php $link = route('workspace-invitations.show', $inv->token); @endphp
                                <li class="rounded-md border border-slate-200 bg-white p-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium text-slate-900">{{ $inv->email }}</div>
                                            <div class="text-xs text-slate-500">
                                                {{ __("app.workspaces.role_{$inv->role}") }} ·
                                                {{ __('app.workspaces.expires_in', ['at' => $inv->expires_at->diffForHumans()]) }}
                                            </div>
                                        </div>
                                        <form method="POST" action="{{ route('workspaces.invitations.revoke', [$workspace, $inv]) }}"
                                              data-confirm="{{ __('app.workspaces.invite_revoke_confirm', ['email' => $inv->email]) }}" data-confirm-danger="1">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-700">{{ __('app.workspaces.invite_revoke') }}</button>
                                        </form>
                                    </div>
                                    <div class="mt-2 flex items-center gap-2" x-data="{ copied: false }">
                                        <input type="text" readonly value="{{ $link }}"
                                               class="flex-1 rounded border-slate-200 bg-slate-50 px-2 py-1 font-mono text-xs text-slate-700">
                                        <button type="button"
                                                @click="navigator.clipboard.writeText('{{ $link }}').then(() => { copied = true; setTimeout(() => copied = false, 1500); })"
                                                class="rounded border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                            <span x-show="!copied">{{ __('app.workspaces.invite_copy') }}</span>
                                            <span x-show="copied" x-cloak class="text-emerald-600">✓ {{ __('app.workspaces.invite_copied') }}</span>
                                        </button>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Invite form --}}
                @if ($canManage)
                    <div class="border-t border-slate-200 px-6 py-4">
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('app.workspaces.invite_heading') }}</h4>
                        <form method="POST" action="{{ route('workspaces.members.invite', $workspace) }}" class="mt-3 flex flex-wrap items-end gap-2">
                            @csrf
                            <div class="flex-1 min-w-[200px]">
                                <label for="email" class="block text-xs font-medium text-slate-600">{{ __('app.workspaces.invite_email_label') }}</label>
                                <input id="email" name="email" type="email" required placeholder="user@example.com"
                                       class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="role" class="block text-xs font-medium text-slate-600">{{ __('app.workspaces.role') }}</label>
                                <select id="role" name="role" class="mt-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    <option value="member">{{ __('app.workspaces.role_member') }}</option>
                                    <option value="admin">{{ __('app.workspaces.role_admin') }}</option>
                                </select>
                            </div>
                            <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                                {{ __('app.workspaces.invite_submit') }}
                            </button>
                        </form>
                        <p class="mt-2 text-xs text-slate-500">{{ __('app.workspaces.invite_hint_v2') }}</p>
                    </div>
                @endif
            </div>

            {{-- Transfer ownership — owner only --}}
            @if ($isOwner)
                @php
                    $transferCandidates = $members->filter(fn ($m) => $m->role !== 'owner');
                @endphp
                <div class="rounded-xl border border-amber-200 bg-amber-50/40 p-6">
                    <h3 class="text-sm font-semibold text-amber-900">{{ __('app.workspaces.transfer_heading') }}</h3>
                    <p class="mt-1 text-xs text-amber-800">{{ __('app.workspaces.transfer_hint') }}</p>
                    @if ($transferCandidates->isEmpty())
                        <p class="mt-3 text-xs text-amber-700">{{ __('app.workspaces.transfer_no_candidates') }}</p>
                    @else
                        <form method="POST" action="{{ route('workspaces.transfer', $workspace) }}" class="mt-4 space-y-3"
                              data-confirm="{{ __('app.workspaces.transfer_confirm') }}" data-confirm-danger="1">
                            @csrf
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="target_user" class="block text-xs font-medium text-slate-600">{{ __('app.workspaces.transfer_to') }}</label>
                                    <select id="target_user" name="target_user" required
                                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                        @foreach ($transferCandidates as $cand)
                                            <option value="{{ $cand->user->id }}">{{ $cand->user->name }} ({{ $cand->user->email }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="transfer_password" class="block text-xs font-medium text-slate-600">{{ __('app.two_factor.current_password') }}</label>
                                    <input id="transfer_password" name="password" type="password" required
                                           class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                            </div>
                            @error('password') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                            <button type="submit" class="rounded-md border border-amber-300 bg-white px-3 py-1.5 text-sm font-medium text-amber-800 hover:bg-amber-50">
                                {{ __('app.workspaces.transfer_submit') }}
                            </button>
                        </form>
                    @endif
                </div>
            @endif

            {{-- Leave / delete --}}
            <div class="rounded-xl border border-rose-200 bg-rose-50/40 p-6">
                <h3 class="text-sm font-semibold text-rose-900">{{ __('app.admin.danger_zone') }}</h3>

                @if (! $isOwner)
                    <p class="mt-1 text-xs text-rose-700">{{ __('app.workspaces.leave_hint') }}</p>
                    <form method="POST" action="{{ route('workspaces.leave', $workspace) }}" class="mt-3"
                          data-confirm="{{ __('app.workspaces.leave_confirm', ['name' => $workspace->name]) }}" data-confirm-danger="1">
                        @csrf
                        <button type="submit" class="rounded-md border border-rose-300 bg-white px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50">
                            {{ __('app.workspaces.leave') }}
                        </button>
                    </form>
                @else
                    <p class="mt-1 text-xs text-rose-700">{{ __('app.workspaces.delete_hint') }}</p>
                    <form method="POST" action="{{ route('workspaces.destroy', $workspace) }}" class="mt-3 space-y-2"
                          data-confirm="{{ __('app.workspaces.delete_confirm', ['name' => $workspace->name]) }}" data-confirm-danger="1">
                        @csrf @method('DELETE')
                        <input type="password" name="password" required placeholder="{{ __('app.two_factor.current_password') }}"
                               class="block w-full max-w-xs rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        @error('password') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                        <button type="submit" class="rounded-md border border-rose-300 bg-white px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50">
                            {{ __('app.workspaces.delete') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
