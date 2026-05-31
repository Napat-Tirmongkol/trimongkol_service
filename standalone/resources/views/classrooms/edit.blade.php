<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('app.classrooms.editHeading') }}: {{ $classroom->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                @include('classrooms._form', [
                    'classroom' => $classroom,
                    'action' => route('classrooms.update', $classroom),
                    'method' => 'PATCH',
                ])
            </div>

            <form method="POST" action="{{ route('classrooms.destroy', $classroom) }}"
                  data-confirm="{{ __('app.classrooms.deleteConfirm') }}" data-confirm-danger="1"
                  class="rounded-xl border border-rose-200 bg-rose-50 p-6">
                @csrf
                @method('DELETE')
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-rose-900">{{ __('app.common.dangerZone') }}</h3>
                        <p class="mt-1 text-xs text-rose-700">{{ __('app.classrooms.deleteWarning') }}</p>
                    </div>
                    <button type="submit"
                            class="rounded-md bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">
                        {{ __('app.classrooms.deleteButton') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
