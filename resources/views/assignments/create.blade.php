<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('classrooms.show', $classroom) }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ $classroom->name }}</a>
            <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">{{ __('app.assignments.createHeading') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                @include('assignments._form', ['classroom' => $classroom, 'action' => route('classrooms.assignments.store', $classroom), 'method' => 'POST'])
            </div>
        </div>
    </div>
</x-app-layout>
