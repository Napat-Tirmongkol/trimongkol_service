@props(['classroom', 'assignment' => null, 'action', 'method' => 'POST'])

<form method="POST" action="{{ $action }}" class="space-y-5">
    @csrf
    @if ($method === 'PUT' || $method === 'PATCH') @method($method) @endif

    <div>
        <label for="name" class="block text-sm font-medium text-slate-700">
            {{ __('app.assignments.field_name') }} <span class="text-rose-500">*</span>
        </label>
        <input id="name" name="name" type="text" required
               value="{{ old('name', $assignment?->name) }}"
               placeholder="{{ __('app.assignments.field_name_placeholder') }}"
               class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="due_date" class="block text-sm font-medium text-slate-700">{{ __('app.assignments.field_due') }}</label>
        <input id="due_date" name="due_date" type="date"
               value="{{ old('due_date', $assignment?->due_date?->format('Y-m-d')) }}"
               class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
        @error('due_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div x-data="{ mode: '{{ old('scoring_mode', $assignment?->scoring_mode ?? 'check') }}' }">
        <label class="block text-sm font-medium text-slate-700">{{ __('app.assignments.field_mode') }}</label>
        <div class="mt-2 space-y-2">
            @foreach (['check', 'fixed', 'custom'] as $mode)
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                    <input type="radio" name="scoring_mode" value="{{ $mode }}" x-model="mode"
                           class="mt-1 border-slate-300 text-brand-600 focus:ring-brand-500">
                    <span>
                        <span class="block text-sm font-medium text-slate-900">{{ __("app.assignments.mode_{$mode}_title") }}</span>
                        <span class="block text-xs text-slate-600">{{ __("app.assignments.mode_{$mode}_desc") }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        @error('scoring_mode') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

        <div x-show="mode === 'fixed' || mode === 'custom'" x-cloak class="mt-3">
            <label for="default_score" class="block text-sm font-medium text-slate-700">
                <span x-show="mode === 'fixed'">{{ __('app.assignments.field_fixed_score') }}</span>
                <span x-show="mode === 'custom'" x-cloak>{{ __('app.assignments.field_default_score') }}</span>
            </label>
            <input id="default_score" name="default_score" type="number" min="0" max="100" step="1"
                   value="{{ old('default_score', $assignment?->default_score) }}"
                   class="mt-1.5 block w-32 rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
            @error('default_score') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="description" class="block text-sm font-medium text-slate-700">{{ __('app.assignments.field_description') }}</label>
        <textarea id="description" name="description" rows="2"
                  class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('description', $assignment?->description) }}</textarea>
    </div>

    <div class="flex items-center justify-end gap-3 pt-2">
        <a href="{{ route('classrooms.show', $classroom) }}" class="text-sm text-slate-600 hover:text-slate-900">
            {{ __('app.common.cancel') }}
        </a>
        <button type="submit" class="rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">
            {{ __('app.common.save') }}
        </button>
    </div>
</form>
