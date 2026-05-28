<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('app.admin.logs_heading') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            <form method="GET" class="flex flex-wrap items-end gap-2">
                <div>
                    <label class="block text-xs font-medium text-slate-600">{{ __('app.admin.logs_action') }}</label>
                    <select name="action" class="mt-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">{{ __('app.admin.filter_all') }}</option>
                        @foreach ($actions as $a)
                            <option value="{{ $a }}" @selected($action === $a)>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    {{ __('app.admin.apply') }}
                </button>
                @if ($action)
                    <a href="{{ route('admin.logs') }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">{{ __('app.admin.clear') }}</a>
                @endif
            </form>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-6 py-3">{{ __('app.admin.logs_when') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.logs_who') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.logs_action') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.logs_target') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.logs_ip') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.logs_meta') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-sm">
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="px-6 py-3 whitespace-nowrap text-xs text-slate-500" title="{{ $log->created_at }}">{{ $log->created_at->diffForHumans() }}</td>
                                    <td class="px-6 py-3">
                                        @if ($log->admin)
                                            <a href="{{ route('admin.users.show', $log->admin) }}" class="text-sm font-medium text-slate-900 hover:text-brand-700">{{ $log->admin->name }}</a>
                                            <div class="text-xs text-slate-500">{{ $log->admin->email }}</div>
                                        @else
                                            <span class="text-xs text-slate-400">{{ __('app.admin.logs_deleted_admin') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3">
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-700">{{ $log->action }}</span>
                                    </td>
                                    <td class="px-6 py-3 text-sm text-slate-700">
                                        @if ($log->target_type)
                                            <span class="text-xs text-slate-500">{{ $log->target_type }}</span>
                                        @endif
                                        <div>{{ $log->target_label ?: ('#' . $log->target_id) }}</div>
                                    </td>
                                    <td class="px-6 py-3 font-mono text-xs text-slate-500">{{ $log->ip ?: '—' }}</td>
                                    <td class="px-6 py-3 text-xs text-slate-500">
                                        @if ($log->metadata)
                                            <code class="break-all">{{ json_encode($log->metadata, JSON_UNESCAPED_UNICODE) }}</code>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.admin.logs_empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $logs->links() }}</div>
        </div>
    </div>
</x-admin-layout>
