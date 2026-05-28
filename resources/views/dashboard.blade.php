<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('app.classrooms.heading') }}
            </h2>
            <a href="{{ route('classrooms.create') }}"
               class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700">
                + {{ __('app.classrooms.add') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($classrooms->isEmpty())
                <div class="rounded-2xl border-2 border-dashed border-slate-300 bg-white p-12 text-center">
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('app.classrooms.emptyTitle') }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ __('app.classrooms.emptyDesc') }}</p>
                    <a href="{{ route('classrooms.create') }}"
                       class="mt-6 inline-block rounded-md bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">
                        + {{ __('app.classrooms.add') }}
                    </a>
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($classrooms as $classroom)
                        <a href="{{ route('classrooms.show', $classroom) }}"
                           class="group rounded-xl border border-slate-200 bg-white p-6 transition hover:border-brand-300 hover:shadow-md">
                            <div class="flex items-start justify-between">
                                <h3 class="text-lg font-semibold text-slate-900 group-hover:text-brand-700">
                                    {{ $classroom->name }}
                                </h3>
                                @if ($classroom->grade_level)
                                    <span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-700">
                                        {{ $classroom->grade_level }}
                                    </span>
                                @endif
                            </div>
                            @if ($classroom->description)
                                <p class="mt-2 line-clamp-2 text-sm text-slate-600">{{ $classroom->description }}</p>
                            @endif
                            <div class="mt-4 flex items-center text-sm text-slate-500">
                                <svg class="mr-1.5 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                {{ $classroom->students_count }} {{ __('app.students.label') }}
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
