<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-3">
            <div>
                <a href="{{ route('admin.users') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.admin.usersHeading') }}</a>
                <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">{{ $user->name }}</h2>
                <p class="text-sm text-slate-500">{{ $user->email }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($user->is_admin)
                    <span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700">admin</span>
                @else
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">teacher</span>
                @endif
                @if ($user->is_active)
                    <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">{{ __('app.admin.status_active') }}</span>
                @else
                    <span class="rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-medium text-rose-700">{{ __('app.admin.status_suspended') }}</span>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            {{-- Stats --}}
            <div class="grid gap-4 sm:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.stat_classrooms') }}</div>
                    <div class="mt-2 text-2xl font-bold text-slate-900">{{ $user->classrooms_count }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.students.heading') }}</div>
                    <div class="mt-2 text-2xl font-bold text-slate-900">{{ $studentCount }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.assignments.heading') }}</div>
                    <div class="mt-2 text-2xl font-bold text-slate-900">{{ $assignmentCount }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.last_login') }}</div>
                    <div class="mt-2 text-sm font-semibold text-slate-900">
                        {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : '—' }}
                    </div>
                    <div class="mt-1 text-xs text-slate-500">
                        {{ __('app.admin.joined') }} {{ $user->created_at->format('d M Y') }}
                    </div>
                </div>
            </div>

            {{-- Admin actions --}}
            @if ($user->id !== auth()->id())
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.actions_heading') }}</h3>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('admin.users.toggle-admin', $user) }}">
                            @csrf
                            <button type="submit"
                                    onclick="return confirm('{{ $user->is_admin ? __('app.admin.confirmDemote', ['name' => $user->name]) : __('app.admin.confirmPromote', ['name' => $user->name]) }}')"
                                    class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium {{ $user->is_admin ? 'text-rose-700 hover:bg-rose-50' : 'text-brand-700 hover:bg-brand-50' }}">
                                {{ $user->is_admin ? __('app.admin.demote') : __('app.admin.promote') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}">
                            @csrf
                            <button type="submit"
                                    onclick="return confirm('{{ $user->is_active ? __('app.admin.confirm_suspend', ['name' => $user->name]) : __('app.admin.confirm_activate', ['name' => $user->name]) }}')"
                                    class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium {{ $user->is_active ? 'text-amber-700 hover:bg-amber-50' : 'text-emerald-700 hover:bg-emerald-50' }}">
                                {{ $user->is_active ? __('app.admin.suspend') : __('app.admin.activate') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.users.password-reset', $user) }}">
                            @csrf
                            <button type="submit"
                                    onclick="return confirm('{{ __('app.admin.confirm_password_reset', ['email' => $user->email]) }}')"
                                    class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                {{ __('app.admin.send_password_reset') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    onclick="return confirm('{{ __('app.admin.confirm_delete_user', ['name' => $user->name]) }}')"
                                    class="rounded-md border border-rose-300 bg-white px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50">
                                {{ __('app.admin.delete_user') }}
                            </button>
                        </form>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">{{ __('app.admin.actions_hint') }}</p>
                </div>
            @endif

            {{-- Classrooms --}}
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.user_classrooms') }}</h3>
                </div>
                @if ($classrooms->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.admin.no_classrooms') }}</div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($classrooms as $c)
                            <li class="flex items-center justify-between gap-3 px-6 py-3">
                                <div>
                                    <div class="text-sm font-medium text-slate-900">{{ $c->name }}</div>
                                    <div class="mt-0.5 text-xs text-slate-500">
                                        {{ $c->students_count }} {{ __('app.students.heading') }}
                                        · {{ $c->assignments_count }} {{ __('app.assignments.heading') }}
                                        · {{ $c->created_at->format('d M Y') }}
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
