<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('app.classrooms.createHeading') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                @include('classrooms._form', ['action' => route('classrooms.store'), 'method' => 'POST'])
            </div>
        </div>
    </div>
</x-app-layout>
