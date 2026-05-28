<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('app.admin.system.heading') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Environment info --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.system.env_heading') }}</h3>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-500">App env</dt><dd class="font-mono">{{ $info['app_env'] }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Debug</dt><dd class="font-mono">{{ $info['app_debug'] ? 'true' : 'false' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">PHP</dt><dd class="font-mono">{{ $info['php_version'] }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Laravel</dt><dd class="font-mono">{{ $info['laravel_version'] }}</dd></div>
                    <div><dt class="text-xs text-slate-500">DB driver</dt><dd class="font-mono">{{ $info['db_driver'] }}</dd></div>
                    <div><dt class="text-xs text-slate-500">DB name</dt><dd class="font-mono">{{ $info['db_database'] }}</dd></div>
                </dl>
            </div>

            {{-- Pull from Git --}}
            <div class="rounded-xl border {{ $webhookConfigured ? 'border-sky-200 bg-sky-50/50' : 'border-slate-200 bg-white shadow-sm' }} p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold {{ $webhookConfigured ? 'text-sky-900' : 'text-slate-900' }}">
                            {{ __('app.admin.system.pull_heading') }}
                        </h3>
                        @if ($webhookConfigured)
                            <p class="mt-1 text-xs text-sky-800">{{ __('app.admin.system.pull_hint') }}</p>
                        @else
                            <p class="mt-1 text-xs text-slate-500">
                                {{ __('app.admin.system.pull_no_url') }}
                                <a href="{{ route('admin.site-settings.edit') }}" class="font-semibold text-brand-700 underline hover:text-brand-800">
                                    {{ __('app.admin.system.pull_configure') }} →
                                </a>
                            </p>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('admin.system.pull') }}"
                          data-confirm="{{ __('app.admin.system.pull_confirm') }}">
                        @csrf
                        <button type="submit"
                                @disabled(! $webhookConfigured)
                                class="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500">
                            {{ __('app.admin.system.run_pull') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Pending migrations --}}
            <div class="rounded-xl border {{ count($pending) ? 'border-amber-200 bg-amber-50/50' : 'border-emerald-200 bg-emerald-50/50' }} p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold {{ count($pending) ? 'text-amber-900' : 'text-emerald-900' }}">
                            {{ __('app.admin.system.pending_heading') }}
                        </h3>
                        @if (count($pending))
                            <p class="mt-1 text-xs text-amber-800">{{ __('app.admin.system.pending_hint', ['n' => count($pending)]) }}</p>
                            <ul class="mt-3 space-y-1 text-xs font-mono text-amber-900">
                                @foreach ($pending as $m)
                                    <li>· {{ $m }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-1 text-xs text-emerald-800">{{ __('app.admin.system.no_pending') }}</p>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('admin.system.migrate') }}"
                          @if (count($pending)) data-confirm="{{ __('app.admin.system.migrate_confirm', ['n' => count($pending)]) }}"
                          @else data-confirm="{{ __('app.admin.system.migrate_confirm_empty') }}" @endif>
                        @csrf
                        <button type="submit"
                                class="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                            {{ __('app.admin.system.run_migrate') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Cache --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.system.cache_heading') }}</h3>
                        <p class="mt-1 text-xs text-slate-500">{{ __('app.admin.system.cache_hint') }}</p>
                    </div>
                    <form method="POST" action="{{ route('admin.system.clear-cache') }}"
                          data-confirm="{{ __('app.admin.system.cache_confirm') }}">
                        @csrf
                        <button type="submit"
                                class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            {{ __('app.admin.system.run_clear_cache') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Last command output --}}
            @if ($lastResult)
                <div class="rounded-xl border border-slate-200 bg-slate-950 p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <code class="text-xs text-slate-400">$ {{ $lastResult['command'] }}</code>
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $lastResult['exit_code'] === 0 ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300' }}">
                            exit {{ $lastResult['exit_code'] }}
                        </span>
                    </div>
                    <pre class="mt-3 overflow-x-auto whitespace-pre-wrap break-words text-xs text-emerald-300">{{ $lastResult['output'] ?: '(no output)' }}</pre>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
