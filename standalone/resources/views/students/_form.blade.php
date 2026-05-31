@props(['classroom', 'student' => null, 'action', 'method' => 'POST'])

<form method="POST" action="{{ $action }}" class="space-y-5">
    @csrf
    @if ($method === 'PUT' || $method === 'PATCH')
        @method($method)
    @endif

    <div class="grid gap-5 sm:grid-cols-3">
        <div class="sm:col-span-1">
            <label for="number" class="block text-sm font-medium text-slate-700">{{ __('app.students.field_number') }}</label>
            <input id="number" name="number" type="text"
                   value="{{ old('number', $student?->number) }}"
                   class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
            @error('number') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="name" class="block text-sm font-medium text-slate-700">
                {{ __('app.students.field_name') }} <span class="text-rose-500">*</span>
            </label>
            <input id="name" name="name" type="text" required
                   value="{{ old('name', $student?->name) }}"
                   class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    @if ($student)
        <div>
            <label for="code" class="block text-sm font-medium text-slate-700">
                {{ __('app.students.field_code') }} <span class="text-rose-500">*</span>
            </label>
            <input id="code" name="code" type="text" required
                   value="{{ old('code', $student->code) }}"
                   class="mt-1.5 block w-full rounded-md border-slate-300 font-mono text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            <p class="mt-1 text-xs text-slate-500">{{ __('app.students.field_code_hint') }}</p>
            @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    @else
        <p class="rounded-md bg-slate-50 px-3 py-2 text-xs text-slate-600">
            {{ __('app.students.code_autogen_hint') }}
        </p>
    @endif

    <div class="flex items-center justify-between pt-2">
        @if ($student)
            <button form="delete-form" type="submit"
                    class="text-sm text-rose-600 hover:text-rose-700">
                {{ __('app.common.delete') }}
            </button>
        @else
            <span></span>
        @endif

        <div class="flex items-center gap-3">
            <a href="{{ route('classrooms.show', $classroom) }}" class="text-sm text-slate-600 hover:text-slate-900">
                {{ __('app.common.cancel') }}
            </a>
            <button type="submit"
                    class="rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                {{ __('app.common.save') }}
            </button>
        </div>
    </div>
</form>

@if ($student)
    <form id="delete-form" method="POST" action="{{ route('classrooms.students.destroy', [$classroom, $student]) }}" class="hidden"
          data-confirm="{{ __('app.students.deleteConfirm') }}" data-confirm-danger="1">
        @csrf
        @method('DELETE')
    </form>
@endif
