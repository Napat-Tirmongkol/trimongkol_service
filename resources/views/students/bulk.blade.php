<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('classrooms.show', $classroom) }}" class="text-xs text-slate-500 hover:text-slate-700">
                ← {{ $classroom->name }}
            </a>
            <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">
                {{ __('app.students.bulk_heading') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-600">{{ __('app.students.bulk_intro') }}</p>

                <div class="mt-4 rounded-lg bg-slate-50 p-3 font-mono text-xs text-slate-600">
                    1 สมชาย ใจดี<br>
                    2 ฤดี ฝนเย็น<br>
                    3, Anna Smith<br>
                    ฟ้าใส ทะเลกว้าง
                </div>

                <form method="POST" action="{{ route('classrooms.students.bulk.store', $classroom) }}" class="mt-5 space-y-5">
                    @csrf
                    <div>
                        <label for="roster" class="block text-sm font-medium text-slate-700">
                            {{ __('app.students.bulk_field') }}
                        </label>
                        <textarea id="roster" name="roster" rows="14" required
                                  placeholder="{{ __('app.students.bulk_placeholder') }}"
                                  class="mt-1.5 block w-full rounded-md border-slate-300 font-mono text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('roster') }}</textarea>
                        @error('roster') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        <p class="mt-2 text-xs text-slate-500">{{ __('app.students.bulk_hint') }}</p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('classrooms.show', $classroom) }}" class="text-sm text-slate-600 hover:text-slate-900">
                            {{ __('app.common.cancel') }}
                        </a>
                        <button type="submit"
                                class="rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                            {{ __('app.students.bulk_submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
