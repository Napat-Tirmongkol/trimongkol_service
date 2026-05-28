<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('app.workspaces.heading') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">

            <div class="rounded-md bg-sky-50 px-4 py-3 text-sm text-sky-800 ring-1 ring-inset ring-sky-200">
                {{ __('app.workspaces.intro') }}
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <ul class="divide-y divide-slate-100">
                    @foreach ($workspaces as $w)
                        @php
                            $isCurrent = $current && $current->id === $w->id;
                            $role = $w->pivot->role;
                        @endphp
                        <li class="flex items-center justify-between gap-3 px-6 py-4 {{ $isCurrent ? 'bg-brand-50/50' : '' }}">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-900">{{ $w->name }}</span>
                                    @if ($role === 'owner')
                                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-200">{{ __('app.workspaces.role_owner') }}</span>
                                    @elseif ($role === 'admin')
                                        <span class="rounded-full bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700 ring-1 ring-inset ring-sky-200">{{ __('app.workspaces.role_admin') }}</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ __('app.workspaces.role_member') }}</span>
                                    @endif
                                    @if ($isCurrent)
                                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">{{ __('app.workspaces.current') }}</span>
                                    @endif
                                </div>
                                <div class="mt-0.5 text-xs text-slate-500">
                                    {{ $w->members_count }} {{ __('app.workspaces.members') }} ·
                                    {{ $w->classrooms_count }} {{ __('app.classrooms.heading') }}
                                </div>
                            </div>
                            @unless ($isCurrent)
                                <form method="POST" action="{{ route('workspaces.switch', $w) }}">
                                    @csrf
                                    <button type="submit" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                        {{ __('app.workspaces.switch') }}
                                    </button>
                                </form>
                            @endunless
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="text-xs text-slate-500">{{ __('app.workspaces.create_hint') }}</p>
        </div>
    </div>
</x-app-layout>
