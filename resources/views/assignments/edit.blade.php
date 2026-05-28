<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('classrooms.assignments.show', [$classroom, $assignment]) }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ $assignment->name }}</a>
            <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">{{ __('app.assignments.editHeading') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                @include('assignments._form', ['classroom' => $classroom, 'assignment' => $assignment, 'action' => route('classrooms.assignments.update', [$classroom, $assignment]), 'method' => 'PATCH'])
            </div>

            <form method="POST" action="{{ route('classrooms.assignments.destroy', [$classroom, $assignment]) }}"
                  onsubmit="return confirm('{{ __('app.assignments.deleteConfirm') }}')"
                  class="rounded-xl border border-rose-200 bg-rose-50 p-6">
                @csrf @method('DELETE')
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-rose-900">{{ __('app.common.dangerZone') }}</h3>
                        <p class="mt-1 text-xs text-rose-700">{{ __('app.assignments.deleteWarning') }}</p>
                    </div>
                    <button type="submit" class="rounded-md bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">
                        {{ __('app.assignments.deleteButton') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
