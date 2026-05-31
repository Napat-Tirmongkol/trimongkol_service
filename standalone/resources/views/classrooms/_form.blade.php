@props(['classroom' => null, 'action', 'method' => 'POST'])

<form method="POST" action="{{ $action }}" class="space-y-5">
    @csrf
    @if ($method === 'PUT' || $method === 'PATCH')
        @method($method)
    @endif

    <div>
        <label for="name" class="block text-sm font-medium text-slate-700">
            {{ __('app.classrooms.field_name') }} <span class="text-rose-500">*</span>
        </label>
        <input id="name" name="name" type="text" required
               value="{{ old('name', $classroom?->name) }}"
               class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="grade_level" class="block text-sm font-medium text-slate-700">
            {{ __('app.classrooms.field_grade') }}
        </label>
        <input id="grade_level" name="grade_level" type="text"
               placeholder="{{ __('app.classrooms.field_grade_placeholder') }}"
               value="{{ old('grade_level', $classroom?->grade_level) }}"
               class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
        @error('grade_level') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="description" class="block text-sm font-medium text-slate-700">
            {{ __('app.classrooms.field_description') }}
        </label>
        <textarea id="description" name="description" rows="3"
                  class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('description', $classroom?->description) }}</textarea>
        @error('description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center justify-end gap-3 pt-2">
        <a href="{{ url()->previous() }}" class="text-sm text-slate-600 hover:text-slate-900">
            {{ __('app.common.cancel') }}
        </a>
        <button type="submit"
                class="rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">
            {{ __('app.common.save') }}
        </button>
    </div>
</form>
