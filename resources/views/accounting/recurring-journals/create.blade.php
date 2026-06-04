<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('accounting.recurring-journals.index') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.recurring_journals') }}</a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.recurring_new') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('accounting.recurring-journals.store') }}"
                  class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm" id="recurring-form">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.recurring_name') }}</label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               placeholder="{{ __('app.accounting.recurring_name_placeholder') }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.frequency') }}</label>
                        <select name="frequency" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                            <option value="monthly" @selected(old('frequency','monthly')==='monthly')>{{ __('app.accounting.freq_monthly') }}</option>
                            <option value="quarterly" @selected(old('frequency')==='quarterly')>{{ __('app.accounting.freq_quarterly') }}</option>
                            <option value="weekly" @selected(old('frequency')==='weekly')>{{ __('app.accounting.freq_weekly') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.next_run') }}</label>
                        <input type="date" name="next_run_date" value="{{ old('next_run_date', now()->addMonth()->startOfMonth()->toDateString()) }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.end_date') }} <span class="text-slate-400">({{ __('app.accounting.optional') }})</span></label>
                        <input type="date" name="end_date" value="{{ old('end_date') }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                </div>

                {{-- Journal lines --}}
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <label class="text-sm font-medium text-slate-700">{{ __('app.accounting.journal_lines') }}</label>
                        <button type="button" id="add-line"
                                class="text-xs font-medium text-brand-700 hover:text-brand-900">+ {{ __('app.accounting.add_line') }}</button>
                    </div>
                    <div id="lines" class="space-y-2">
                        @php $oldLines = old('lines', [['account_id'=>'','debit'=>'','credit'=>'','description'=>''],['account_id'=>'','debit'=>'','credit'=>'','description'=>'']]); @endphp
                        @foreach ($oldLines as $i => $line)
                            <div class="line-row grid grid-cols-12 gap-2">
                                <div class="col-span-4">
                                    <select name="lines[{{ $i }}][account_id]"
                                            class="block w-full rounded-lg border border-slate-300 px-2 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                                        <option value="">— {{ __('app.accounting.col_account') }} —</option>
                                        @foreach ($accounts as $acc)
                                            <option value="{{ $acc->id }}" @selected(($line['account_id'] ?? '') == $acc->id)>
                                                {{ $acc->code }} {{ $acc->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-3">
                                    <input type="number" name="lines[{{ $i }}][debit]" value="{{ $line['debit'] ?? '' }}"
                                           step="0.01" min="0" placeholder="{{ __('app.accounting.col_debit') }}"
                                           class="block w-full rounded-lg border border-slate-300 px-2 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                                </div>
                                <div class="col-span-3">
                                    <input type="number" name="lines[{{ $i }}][credit]" value="{{ $line['credit'] ?? '' }}"
                                           step="0.01" min="0" placeholder="{{ __('app.accounting.col_credit') }}"
                                           class="block w-full rounded-lg border border-slate-300 px-2 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                                </div>
                                <div class="col-span-2 flex items-center">
                                    <button type="button" class="remove-line text-xs text-rose-400 hover:text-rose-600">✕</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('accounting.recurring-journals.index') }}"
                       class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        {{ __('app.accounting.cancel') }}
                    </a>
                    <button type="submit"
                            class="rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                        {{ __('app.accounting.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function () {
        const accounts = @json($accounts->map(fn($a) => ['id' => $a->id, 'code' => $a->code, 'name' => $a->name]));
        let idx = {{ count($oldLines) }};

        function buildSelect(name) {
            const sel = document.createElement('select');
            sel.name = name;
            sel.className = 'block w-full rounded-lg border border-slate-300 px-2 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400';
            const blank = document.createElement('option');
            blank.value = '';
            blank.textContent = '— {{ __("app.accounting.col_account") }} —';
            sel.appendChild(blank);
            accounts.forEach(a => {
                const opt = document.createElement('option');
                opt.value = a.id;
                opt.textContent = a.code + ' ' + a.name;
                sel.appendChild(opt);
            });
            return sel;
        }

        document.getElementById('add-line').addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'line-row grid grid-cols-12 gap-2';
            row.innerHTML = `
                <div class="col-span-4"></div>
                <div class="col-span-3"><input type="number" name="lines[${idx}][debit]" step="0.01" min="0" placeholder="{{ __("app.accounting.col_debit") }}" class="block w-full rounded-lg border border-slate-300 px-2 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400"></div>
                <div class="col-span-3"><input type="number" name="lines[${idx}][credit]" step="0.01" min="0" placeholder="{{ __("app.accounting.col_credit") }}" class="block w-full rounded-lg border border-slate-300 px-2 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400"></div>
                <div class="col-span-2 flex items-center"><button type="button" class="remove-line text-xs text-rose-400 hover:text-rose-600">✕</button></div>`;
            row.querySelector('div:first-child').appendChild(buildSelect(`lines[${idx}][account_id]`));
            document.getElementById('lines').appendChild(row);
            idx++;
        });

        document.getElementById('lines').addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-line')) {
                e.target.closest('.line-row').remove();
            }
        });
    })();
    </script>
</x-app-layout>
