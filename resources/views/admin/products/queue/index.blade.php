<x-admin-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.products.queue.label') }}</div>
            <h2 class="mt-0.5 text-xl font-semibold leading-tight text-gray-800">{{ __('app.admin.products.queue.tab_queues') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <form method="GET" action="{{ route('admin.queue.index') }}" class="flex gap-2">
                <input name="q" type="search" value="{{ $q }}"
                       placeholder="{{ __('app.admin.products.queue.search_placeholder') }}"
                       class="w-full max-w-sm rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">{{ __('app.admin.search') }}</button>
            </form>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                @if ($queues->isEmpty())
                    <div class="px-6 py-10 text-center text-sm text-slate-500">{{ __('app.admin.products.queue.queues_empty') }}</div>
                @else
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-6 py-3">{{ __('app.queue.name_label') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.owner') }}</th>
                                <th class="px-6 py-3 text-center">{{ __('app.queue.counters') }}</th>
                                <th class="px-6 py-3 text-center">{{ __('app.queue.waiting') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($queues as $queue)
                                <tr>
                                    <td class="px-6 py-3 font-medium text-slate-900">{{ $queue->name }}</td>
                                    <td class="px-6 py-3 text-slate-500">
                                        @if ($queue->workspace) <div>{{ $queue->workspace->name }}</div> @endif
                                        @if ($queue->user) <div class="text-xs text-slate-400">{{ $queue->user->email }}</div> @endif
                                    </td>
                                    <td class="px-6 py-3 text-center text-slate-600">{{ $queue->counters_count }}</td>
                                    <td class="px-6 py-3 text-center text-slate-600">{{ $queue->waiting_count }}</td>
                                    <td class="px-6 py-3 text-right">
                                        <form method="POST" action="{{ route('admin.queue.destroy', $queue) }}"
                                              data-confirm="{{ __('app.admin.products.queue.delete_confirm') }}" data-confirm-danger="1">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{ $queues->links() }}
        </div>
    </div>
</x-admin-layout>
