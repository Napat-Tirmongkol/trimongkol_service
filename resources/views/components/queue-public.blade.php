<?php

use App\Models\Queue;
use App\Models\QueueTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public Queue $queue;

    // Server-managed only (set from the session in mount / issueTicket) — never
    // bound to client input, so lock them against tampering by a guest who
    // could otherwise cancel someone else's ticket by spoofing an id.
    #[Locked]
    public ?int $myTicketId = null;

    #[Locked]
    public ?string $announcedStatus = null;

    public function mount(Queue $queue): void
    {
        $this->queue = $queue;
        $this->myTicketId = (int) Session::get($this->sessionKey()) ?: null;
    }

    private function sessionKey(): string
    {
        return "queue.public.{$this->queue->id}.ticket";
    }

    private function myTicket(): ?QueueTicket
    {
        if (! $this->myTicketId) {
            return null;
        }
        return QueueTicket::where('queue_id', $this->queue->id)
            ->whereKey($this->myTicketId)
            ->first();
    }

    public function issueTicket(): void
    {
        if (! $this->queue->is_active) {
            return;
        }

        $existing = $this->myTicket();
        if ($existing && in_array($existing->status, ['waiting', 'called', 'serving'])) {
            return; // one active ticket per device
        }

        $ticket = DB::transaction(function () {
            $queue = Queue::whereKey($this->queue->id)->lockForUpdate()->first();
            $number = $queue->last_number + 1;
            $queue->update(['last_number' => $number]);

            return $queue->tickets()->create(['number' => $number, 'status' => 'waiting']);
        });

        $this->myTicketId = $ticket->id;
        Session::put($this->sessionKey(), $ticket->id);
        $this->announcedStatus = null;
    }

    public function cancelTicket(): void
    {
        $ticket = $this->myTicket();
        if ($ticket && $ticket->status === 'waiting') {
            $ticket->update(['status' => 'skipped']);
        }

        $this->myTicketId = null;
        Session::forget($this->sessionKey());
        $this->announcedStatus = null;
    }

    public function refresh(): void
    {
        $ticket = $this->myTicket();

        if ($ticket && $ticket->status === 'called' && $this->announcedStatus !== 'called') {
            $this->announcedStatus = 'called';
            $this->dispatch('queue-your-turn',
                code: $this->queue->code($ticket->number),
                counter: optional($ticket->counter)->label ?? '',
            );
        }

        if (! $ticket || in_array($ticket->status, ['done', 'skipped'])) {
            $this->announcedStatus = null;
        }
    }

    public function with(): array
    {
        $counters = $this->queue->counters()->get();

        $current = QueueTicket::where('queue_id', $this->queue->id)
            ->whereIn('status', QueueTicket::ACTIVE_STATUSES)
            ->with('counter')
            ->get()
            ->keyBy('counter_id');

        $myTicket = $this->myTicket();
        $aheadCount = 0;
        if ($myTicket && $myTicket->status === 'waiting') {
            $aheadCount = QueueTicket::where('queue_id', $this->queue->id)
                ->where('status', 'waiting')
                ->where('number', '<', $myTicket->number)
                ->count();
        }

        return [
            'counters' => $counters,
            'current' => $current,
            'myTicket' => $myTicket,
            'aheadCount' => $aheadCount,
        ];
    }
};
?>

<div wire:poll.4s="refresh" x-data="queuePublic()" class="mx-auto w-full max-w-md px-4 py-6">
    {{-- Queue title --}}
    <div class="text-center">
        <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-sm font-medium text-white/90 ring-1 ring-white/20 backdrop-blur">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/></svg>
            {{ $queue->name }}
        </div>
    </div>

    @if (! $queue->is_active)
        <div class="mt-6 rounded-3xl bg-white p-8 text-center shadow-xl">
            <div class="text-5xl">🚪</div>
            <p class="mt-4 text-lg font-semibold text-slate-800">{{ __('app.queue.closed') }}</p>
        </div>
    @else
        {{-- My ticket --}}
        @if ($myTicket && in_array($myTicket->status, ['waiting', 'called', 'serving']))
            @php $isMyTurn = in_array($myTicket->status, ['called', 'serving']); @endphp
            <div class="mt-6 overflow-hidden rounded-3xl bg-white shadow-xl">
                <div class="p-6 text-center {{ $isMyTurn ? 'bg-gradient-to-br from-emerald-500 to-emerald-600 text-white' : '' }}">
                    <div class="text-xs font-medium uppercase tracking-wider {{ $isMyTurn ? 'text-emerald-50' : 'text-slate-500' }}">
                        {{ $isMyTurn ? __('app.queue.your_turn') : __('app.queue.your_ticket') }}
                    </div>
                    <div class="mt-2 text-7xl font-extrabold tracking-tight tabular-nums {{ $isMyTurn ? 'text-white' : 'text-slate-900' }}">
                        {{ $queue->prefix }}{{ $myTicket->padded_number }}
                    </div>

                    @if ($isMyTurn)
                        <div class="mt-3 text-lg font-semibold">
                            {{ __('app.queue.go_to_counter') }}
                            <span class="ml-1 rounded-lg bg-white/25 px-3 py-1">{{ optional($myTicket->counter)->label ?? '—' }}</span>
                        </div>
                    @else
                        <div class="mt-3 inline-flex items-center gap-2 rounded-full bg-amber-50 px-4 py-1.5 text-sm font-semibold text-amber-700">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-500"></span>
                            </span>
                            {{ __('app.queue.ahead') }} <span class="tabular-nums">{{ $aheadCount }}</span> {{ __('app.queue.ahead_unit') }}
                        </div>
                    @endif
                </div>

                @unless ($isMyTurn)
                    <div class="border-t border-slate-100 p-4">
                        <button type="button" wire:click="cancelTicket"
                                wire:confirm="{{ __('app.queue.cancel_confirm') }}"
                                class="w-full rounded-xl px-4 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-50">
                            {{ __('app.queue.cancel_ticket') }}
                        </button>
                    </div>
                @endunless
            </div>
        @else
            {{-- No active ticket → issue one --}}
            <div class="mt-6 rounded-3xl bg-white p-6 text-center shadow-xl">
                <p class="text-sm text-slate-500">{{ $queue->name }}</p>
                <button type="button" wire:click="issueTicket" @click="unlockAudio()" wire:loading.attr="disabled"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-2xl bg-brand-600 px-6 py-5 text-xl font-extrabold text-white shadow-lg shadow-brand-600/30 transition hover:bg-brand-700 active:scale-[0.99] disabled:opacity-60">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><line x1="12" y1="9" x2="12" y2="15"/><line x1="9" y1="12" x2="15" y2="12"/></svg>
                    {{ __('app.queue.get_ticket') }}
                </button>
            </div>
        @endif

        {{-- Now serving board --}}
        @if ($counters->isNotEmpty())
            <div class="mt-6 rounded-3xl bg-white/95 p-5 shadow-xl backdrop-blur">
                <h3 class="text-center text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('app.queue.now_serving') }}</h3>
                <div class="mt-3 grid grid-cols-2 gap-2.5">
                    @foreach ($counters as $counter)
                        @php $ticket = $current[$counter->id] ?? null; @endphp
                        <div class="rounded-2xl border border-slate-200 p-3 text-center">
                            <div class="text-[11px] font-medium uppercase tracking-wider text-slate-400">{{ __('app.queue.counter_word') }} {{ $counter->label }}</div>
                            @if ($ticket)
                                <div class="mt-0.5 text-2xl font-extrabold text-slate-900 tabular-nums">{{ $queue->prefix }}{{ $ticket->padded_number }}</div>
                            @else
                                <div class="mt-0.5 text-lg font-bold text-slate-300">{{ __('app.queue.empty_counter') }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    @script
    <script>
        Alpine.data('queuePublic', () => ({
            audioCtx: null,

            init() {
                this.$wire.on('queue-your-turn', (payload) => {
                    const p = Array.isArray(payload) ? payload[0] : payload;
                    this.unlockAudio();
                    this.chime();
                    if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
                    this.speak(`@js(__('app.queue.voice_your_turn'))${p && p.counter ? ' ' + p.counter : ''}`);
                });
                window.addEventListener('pointerdown', () => this.unlockAudio(), { once: true });
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
                [880, 1175, 1568].forEach((freq, i) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.value = freq;
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    const start = t + i * 0.16;
                    gain.gain.setValueAtTime(0.0001, start);
                    gain.gain.exponentialRampToValueAtTime(0.35, start + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.15);
                    osc.start(start);
                    osc.stop(start + 0.17);
                });
            },

            speak(text) {
                if (!('speechSynthesis' in window)) return;
                const u = new SpeechSynthesisUtterance(text);
                u.lang = @js(app()->getLocale() === 'th' ? 'th-TH' : 'en-US');
                u.rate = 0.95;
                const voice = window.speechSynthesis.getVoices().find((v) => v.lang && v.lang.toLowerCase().startsWith(u.lang.slice(0, 2)));
                if (voice) u.voice = voice;
                window.speechSynthesis.cancel();
                window.speechSynthesis.speak(u);
            },
        }));
    </script>
    @endscript
</div>
