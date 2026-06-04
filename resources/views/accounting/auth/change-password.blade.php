<x-accounting-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.change_password') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-md px-4 sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('accounting.password.update') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ __('app.accounting.current_password') }}</label>
                        <input type="password" name="current_password" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none @error('current_password') border-rose-400 @enderror">
                        @error('current_password')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ __('app.accounting.new_password') }}</label>
                        <input type="password" name="password" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ __('app.accounting.confirm_password') }}</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
                    </div>

                    <button type="submit"
                            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                        {{ __('app.accounting.save') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-accounting-layout>
