<?php

use App\Models\Queue;
use App\Models\QueueCounter;
use App\Models\QueueTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public Queue $queue;

    // Set via useCounter() (validated server-side) + persisted in session —
    // never bound to client input, so lock it.
    #[Locked]
    public ?int $counterId = null;

    public ?string $feedback = null;

    public function mount(Queue $queue): void
    {
        $this->queue = $queue;
        $this->counterId = (int) Session::get($this->sessionKey()) ?: null;

        // Default to the first active counter so a single-counter shop can
        // just start calling without picking anything.
        if (! $this->counterId) {
            $this->counterId = $queue->counters()->where('is_active', true)->value('id');
            if ($this->counterId) {
                Session::put($this->sessionKey(), $this->counterId);
            }
        }
    }

    private function sessionKey(): string
    {
        return "queue.{$this->queue->id}.counter";
    }

    public function useCounter(int $id): void
    {
        if ($this->queue->counters()->whereKey($id)->exists()) {
            $this->counterId = $id;
            Session::put($this->sessionKey(), $id);
        }
    }

    private function activeCounter(): ?QueueCounter
    {
        return $this->counterId
            ? $this->queue->counters()->whereKey($this->counterId)->first()
            : null;
    }

    public function callNext(): void
    {
        $counter = $this->activeCounter();
        if (! $counter) {
            $this->feedback = __('app.queue.pick_counter');
            return;
        }

        $next = DB::transaction(function () use ($counter) {
            // Calling the next person implies the one this counter was serving
            // is done.
            QueueTicket::where('queue_id', $this->queue->id)
                ->where('counter_id', $counter->id)
                ->whereIn('status', QueueTicket::ACTIVE_STATUSES)
                ->update(['status' => 'done', 'served_at' => now()]);

            $ticket = QueueTicket::where('queue_id', $this->queue->id)
                ->where('status', 'waiting')
                ->orderBy('number')
                ->lockForUpdate()
                ->first();

            if ($ticket) {
                $ticket->update([
                    'status' => 'called',
                    'counter_id' => $counter->id,
                    'called_at' => now(),
                ]);
            }

            return $ticket;
        });

        if ($next) {
            $this->feedback = null;
            $this->announce($next, $counter);
        } else {
            $this->feedback = __('app.queue.no_waiting');
        }
    }

    public function recall(): void
    {
        $counter = $this->activeCounter();
        if (! $counter) {
            return;
        }

        $current = QueueTicket::where('queue_id', $this->queue->id)
            ->where('counter_id', $counter->id)
            ->whereIn('status', QueueTicket::ACTIVE_STATUSES)
            ->orderByDesc('called_at')
            ->first();

        if ($current) {
            $this->announce($current, $counter);
        }
    }

    public function skipCurrent(): void
    {
        $counter = $this->activeCounter();
        if (! $counter) {
            return;
        }

        QueueTicket::where('queue_id', $this->queue->id)
            ->where('counter_id', $counter->id)
            ->whereIn('status', QueueTicket::ACTIVE_STATUSES)
            ->update(['status' => 'skipped']);

        $this->feedback = null;
    }

    public function toggleVoice(): void
    {
        $this->queue->update(['voice_enabled' => ! $this->queue->voice_enabled]);
    }

    private function announce(QueueTicket $ticket, QueueCounter $counter): void
    {
        $this->dispatch('queue-called',
            number: $ticket->number,
            prefix: $this->queue->prefix,
            code: $this->queue->code($ticket->number),
            counter: $counter->label,
            voice: (bool) $this->queue->voice_enabled,
        );
    }

    public function with(): array
    {
        $counters = $this->queue->counters()->get();

        $current = QueueTicket::where('queue_id', $this->queue->id)
            ->whereIn('status', QueueTicket::ACTIVE_STATUSES)
            ->get()
            ->keyBy('counter_id');

        $waiting = QueueTicket::where('queue_id', $this->queue->id)
            ->where('status', 'waiting')
            ->orderBy('number')
            ->limit(40)
            ->get();

        return [
            'counters' => $counters,
            'current' => $current,
            'waiting' => $waiting,
            'waitingCount' => QueueTicket::where('queue_id', $this->queue->id)->where('status', 'waiting')->count(),
            'servedToday' => QueueTicket::where('queue_id', $this->queue->id)
                ->where('status', 'done')
                ->whereDate('served_at', now()->toDateString())
                ->count(),
        ];
    }
};
?>

<div wire:poll.3s x-data="queueControl()" class="space-y-6">
    {{-- Counter selector --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm font-semibold text-slate-500">{{ __('app.queue.your_counter') }}</div>
            <div class="flex items-center gap-2">
                <button type="button" @click="testVoice()"
                        class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-200">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
                    {{ __('app.queue.voice_test') }}
                </button>
                <button type="button" wire:click="toggleVoice"
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold ring-1 transition
                               {{ $queue->voice_enabled ? 'bg-brand-50 text-brand-700 ring-brand-200' : 'bg-slate-100 text-slate-500 ring-slate-200' }}">
                    @if ($queue->voice_enabled)
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
                        {{ __('app.queue.voice_on') }}
                    @else
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>
                        {{ __('app.queue.voice_off') }}
                    @endif
                </button>
            </div>
        </div>

        @if ($counters->isEmpty())
            <div class="mt-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">
                {{ __('app.queue.no_counters') }}
                <a href="{{ route('queues.edit', $queue) }}" class="font-semibold underline underline-offset-2">{{ __('app.queue.settings') }}</a>
            </div>
        @else
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($counters as $counter)
                    <button type="button" wire:click="useCounter({{ $counter->id }})"
                            class="rounded-full px-4 py-2 text-sm font-semibold ring-1 transition
                                   {{ $counterId === $counter->id ? 'bg-slate-900 text-white ring-slate-900' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}">
                        {{ __('app.queue.counter_word') }} {{ $counter->label }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Now serving board --}}
    <div>
        <h3 class="mb-2 text-sm font-semibold text-slate-500">{{ __('app.queue.now_serving') }}</h3>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($counters as $counter)
                @php $ticket = $current[$counter->id] ?? null; @endphp
                <div class="rounded-2xl border p-4 text-center shadow-sm transition
                            {{ $counterId === $counter->id ? 'border-brand-300 bg-brand-50/60 ring-1 ring-brand-200' : 'border-slate-200 bg-white' }}">
                    <div class="text-xs font-medium uppercase tracking-wider text-slate-500">
                        {{ __('app.queue.counter_word') }} {{ $counter->label }}
                    </div>
                    @if ($ticket)
                        <div class="mt-1 text-4xl font-extrabold tracking-tight text-slate-900 tabular-nums">{{ $queue->prefix }}{{ $ticket->padded_number }}</div>
                    @else
                        <div class="mt-1 text-2xl font-bold text-slate-300">{{ __('app.queue.empty_counter') }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Primary actions --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        @if ($feedback)
            <div class="mb-4 rounded-xl bg-slate-100 px-4 py-2.5 text-center text-sm font-medium text-slate-600">{{ $feedback }}</div>
        @endif

        <button type="button" wire:click="callNext" wire:loading.attr="disabled" @disabled($counters->isEmpty())
                class="flex w-full items-center justify-center gap-3 rounded-2xl bg-brand-600 px-6 py-6 text-2xl font-extrabold text-white shadow-lg shadow-brand-600/30 transition hover:bg-brand-700 active:scale-[0.99] disabled:opacity-60">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            {{ __('app.queue.call_next') }}
        </button>

        <div class="mt-3 grid grid-cols-2 gap-3">
            <button type="button" wire:click="recall"
                    class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                {{ __('app.queue.recall') }}
            </button>
            <button type="button" wire:click="skipCurrent"
                    class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-500 transition hover:bg-slate-50">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 4 15 12 5 20 5 4"/><line x1="19" y1="5" x2="19" y2="19"/></svg>
                {{ __('app.queue.skip') }}
            </button>
        </div>
    </div>

    {{-- Stats + waiting list --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 text-center shadow-sm">
            <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.queue.waiting') }}</div>
            <div class="mt-1 text-3xl font-bold text-amber-600 tabular-nums">{{ $waitingCount }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 text-center shadow-sm">
            <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.queue.served_today') }}</div>
            <div class="mt-1 text-3xl font-bold text-emerald-600 tabular-nums">{{ $servedToday }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 text-center shadow-sm">
            <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.queue.counters') }}</div>
            <div class="mt-1 text-3xl font-bold text-slate-900 tabular-nums">{{ $counters->count() }}</div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-500">{{ __('app.queue.waiting_list') }}</h3>
        @if ($waiting->isEmpty())
            <div class="mt-3 rounded-xl bg-emerald-50 px-4 py-6 text-center text-sm text-emerald-700">{{ __('app.queue.no_waiting') }}</div>
        @else
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($waiting as $ticket)
                    <span wire:key="wait-{{ $ticket->id }}"
                          class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-700 tabular-nums {{ $loop->first ? 'ring-2 ring-brand-400' : '' }}">
                        {{ $queue->prefix }}{{ $ticket->padded_number }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    @script
    <script>
        Alpine.data('queueControl', () => ({
            audioCtx: null,
            voices: [],
            callWord: @js(__('app.queue.voice_call', [], 'th')),
            counterWord: @js(__('app.queue.voice_counter', [], 'th')),
            sampleNumber: @js(($queue->prefix ? $queue->prefix.' ' : '').'1'),

            init() {
                // Voices often aren't ready on first paint — (re)load them when
                // the browser fires voiceschanged so we can pick a Thai voice.
                this.loadVoices();
                if ('speechSynthesis' in window) {
                    window.speechSynthesis.addEventListener('voiceschanged', () => this.loadVoices());
                }

                // Re-announce only when THIS operator calls — other counters'
                // calls arrive via polling and must stay silent here.
                this.$wire.on('queue-called', (payload) => {
                    const p = Array.isArray(payload) ? payload[0] : payload;
                    this.unlockAudio();
                    this.chime();
                    if (p && p.voice) {
                        const spoken = (p.prefix ? p.prefix + ' ' : '') + p.number;
                        this.speak(this.callWord + ' ' + spoken + ' ' + this.counterWord + ' ' + p.counter);
                    }
                });
                // Browsers need a gesture before audio can play.
                window.addEventListener('pointerdown', () => this.unlockAudio(), { once: true });
            },

            loadVoices() {
                try { this.voices = window.speechSynthesis.getVoices() || []; } catch (e) { this.voices = []; }
            },

            thaiVoice() {
                if (!this.voices.length) this.loadVoices();
                return this.voices.find((v) => {
                    const lang = (v.lang || '').toLowerCase().replace('_', '-');
                    const name = (v.name || '').toLowerCase();
                    return lang.startsWith('th') || name.includes('thai') || name.includes('ไทย');
                }) || null;
            },

            // Sample call so staff can verify the speaker + that a Thai voice
            // actually exists on this device.
            testVoice() {
                this.unlockAudio();
                this.chime();
                const ok = this.speak(this.callWord + ' ' + this.sampleNumber + ' ' + this.counterWord + ' 1');
                if (!ok) {
                    console.warn('Queue: no Thai TTS voice on this device. Available voices:',
                        (this.voices || []).map((v) => v.name + ' [' + v.lang + ']'));
                    if (window.flashToast) window.flashToast({ message: @js(__('app.queue.voice_no_thai')), type: 'warning', timer: 6000 });
                }
            },

            unlockAudio() {
                try {
                    this.audioCtx = this.audioCtx || new (window.AudioContext || window.webkitAudioContext)();
                    if (this.audioCtx.state === 'suspended') this.audioCtx.resume();
                } catch (e) { /* ignore */ }
            },

            chime() {
                if (!this.audioCtx) return;
                const ctx = this.audioCtx;
                const t = ctx.currentTime;
                [880, 1175].forEach((freq, i) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.value = freq;
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    const start = t + i * 0.18;
                    gain.gain.setValueAtTime(0.0001, start);
                    gain.gain.exponentialRampToValueAtTime(0.32, start + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.16);
                    osc.start(start);
                    osc.stop(start + 0.18);
                });
            },

            speak(text) {
                if (!('speechSynthesis' in window)) return false;
                const u = new SpeechSynthesisUtterance(text);
                u.lang = 'th-TH';
                u.rate = 0.92;
                const thai = this.thaiVoice();
                if (thai) u.voice = thai;
                window.speechSynthesis.cancel();
                window.speechSynthesis.speak(u);
                return !!thai;
            },
        }));
    </script>
    @endscript
</div>
