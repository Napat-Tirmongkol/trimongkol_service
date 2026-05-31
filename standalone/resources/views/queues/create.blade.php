<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.queue.create') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('queues.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">
                            {{ __('app.queue.name_label') }} <span class="text-rose-500">*</span>
                        </label>
                        <input id="name" name="name" type="text" required autofocus
                               value="{{ old('name') }}"
                               placeholder="{{ __('app.queue.name_placeholder') }}"
                               class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="prefix" class="block text-sm font-medium text-slate-700">
                            {{ __('app.queue.prefix_label') }}
                        </label>
                        <input id="prefix" name="prefix" type="text" maxlength="8"
                               value="{{ old('prefix') }}"
                               placeholder="A"
                               class="mt-1.5 block w-40 rounded-md border-slate-300 uppercase shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <p class="mt-1 text-xs text-slate-500">{{ __('app.queue.prefix_hint') }}</p>
                        @error('prefix') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('queues.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                            {{ __('app.common.cancel') }}
                        </a>
                        <button type="submit"
                                class="rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                            {{ __('app.common.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
