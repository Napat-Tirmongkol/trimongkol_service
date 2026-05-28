<?php

use App\Models\Assignment;
use App\Models\Student;
use App\Models\Submission;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public Assignment $assignment;

    public string $code = '';

    public ?string $message = null;
    public string $messageType = 'success';
    public ?int $lastStudentId = null;
    public ?string $lastStudentName = null;
    public ?string $lastStudentNumber = null;
    public ?int $lastScore = null;

    public bool $askScore = false;
    public ?int $pendingStudentId = null;
    public ?string $pendingStudentName = null;
    public ?string $pendingStudentNumber = null;
    public ?string $pendingScore = null;
    public ?string $scoreError = null;

    public function mount(Assignment $assignment): void
    {
        $this->assignment = $assignment;
    }

    public function scan(): void
    {
        $code = strtoupper(trim($this->code));
        $this->code = '';

        if ($code === '') {
            return;
        }

        $student = Student::where('classroom_id', $this->assignment->classroom_id)
            ->where('code', $code)
            ->first();

        if (! $student) {
            $this->resetFeedback();
            $this->message = __('app.scan.not_found', ['code' => $code]);
            $this->messageType = 'error';
            return;
        }

        if (Submission::where('assignment_id', $this->assignment->id)
                ->where('student_id', $student->id)
                ->exists()) {
            $this->resetFeedback();
            $this->message = $student->name;
            $this->messageType = 'duplicate';
            $this->lastStudentId = $student->id;
            $this->lastStudentName = $student->name;
            $this->lastStudentNumber = $student->number;
            return;
        }

        if ($this->assignment->scoring_mode === 'custom') {
            $this->askScore = true;
            $this->pendingStudentId = $student->id;
            $this->pendingStudentName = $student->name;
            $this->pendingStudentNumber = $student->number;
            $this->pendingScore = (string) ($this->assignment->default_score ?? '');
            $this->scoreError = null;
            return;
        }

        $this->commitSubmission($student->id, $this->assignment->scoring_mode === 'fixed' ? $this->assignment->default_score : null);
    }

    public function saveScore(): void
    {
        if (! $this->askScore || ! $this->pendingStudentId) {
            return;
        }

        $raw = trim((string) $this->pendingScore);
        if ($raw === '') {
            $score = null;
        } elseif (! ctype_digit($raw) || (int) $raw < 0 || (int) $raw > 100) {
            $this->scoreError = __('app.scan.score_invalid');
            return;
        } else {
            $score = (int) $raw;
        }

        $this->commitSubmission($this->pendingStudentId, $score);

        $this->askScore = false;
        $this->pendingStudentId = null;
        $this->pendingStudentName = null;
        $this->pendingStudentNumber = null;
        $this->pendingScore = null;
        $this->scoreError = null;
    }

    public function cancelScore(): void
    {
        $this->askScore = false;
        $this->pendingStudentId = null;
        $this->pendingStudentName = null;
        $this->pendingStudentNumber = null;
        $this->pendingScore = null;
        $this->scoreError = null;
    }

    public function undo(int $studentId): void
    {
        $submission = Submission::where('assignment_id', $this->assignment->id)
            ->where('student_id', $studentId)
            ->first();

        if (! $submission) {
            return;
        }

        $student = $submission->student;
        $submission->delete();

        $this->resetFeedback();
        $this->message = __('app.scan.undone', ['name' => $student?->name ?? '']);
        $this->messageType = 'undo';
    }

    private function commitSubmission(int $studentId, ?int $score): void
    {
        $student = Student::find($studentId);
        if (! $student) {
            return;
        }

        Submission::create([
            'assignment_id' => $this->assignment->id,
            'student_id' => $studentId,
            'submitted_at' => now(),
            'score' => $score,
        ]);

        $this->resetFeedback();
        $this->message = $student->name;
        $this->messageType = 'success';
        $this->lastStudentId = $student->id;
        $this->lastStudentName = $student->name;
        $this->lastStudentNumber = $student->number;
        $this->lastScore = $score;
    }

    private function resetFeedback(): void
    {
        $this->message = null;
        $this->lastStudentId = null;
        $this->lastStudentName = null;
        $this->lastStudentNumber = null;
        $this->lastScore = null;
    }

    public function with(): array
    {
        $totalCount = $this->assignment->classroom->students()->count();
        $submitted = $this->assignment->submissions()
            ->with('student')
            ->latest('submitted_at')
            ->get();
        $submittedIds = $submitted->pluck('student_id')->all();
        $notSubmitted = $this->assignment->classroom->students()
            ->whereNotIn('id', $submittedIds)
            ->get();

        return [
            'totalCount' => $totalCount,
            'submittedCount' => count($submittedIds),
            'submitted' => $submitted,
            'notSubmitted' => $notSubmitted,
        ];
    }
};
?>

<div x-data="scanDashboard()" class="min-h-screen bg-slate-900 text-white">
    <header class="sticky top-0 z-20 border-b border-slate-700/60 bg-slate-900/95 backdrop-blur">
        <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-3">
            <a href="{{ route('classrooms.assignments.show', [$assignment->classroom_id, $assignment]) }}"
               class="flex items-center gap-2 text-sm text-slate-300 hover:text-white">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                <span class="hidden sm:inline">{{ __('app.common.back') }}</span>
            </a>
            <div class="text-center">
                <div class="text-xs text-slate-400">{{ $assignment->classroom->name }}</div>
                <div class="text-sm font-semibold">{{ $assignment->name }}</div>
                <div class="mt-0.5 text-[10px] uppercase tracking-wider text-slate-500">
                    {{ __("app.assignments.mode_{$assignment->scoring_mode}_title") }}
                    @if ($assignment->scoring_mode === 'fixed' && $assignment->default_score !== null)
                        · {{ $assignment->default_score }}
                    @endif
                </div>
            </div>
            <div class="text-right">
                <div class="text-lg font-bold tabular-nums">{{ $submittedCount }} / {{ $totalCount }}</div>
                <div class="text-xs text-slate-400">{{ __('app.scan.submitted_label') }}</div>
            </div>
        </div>
    </header>

    <style>
        #scanner-region {
            background: #000 !important;
            position: relative !important;
            overflow: hidden !important;
            width: 100% !important;
            height: 100% !important;
        }
        #scanner-region video {
            width: 100% !important;
            height: 100% !important;
            min-width: 100% !important;
            min-height: 100% !important;
            object-fit: cover !important;
            position: absolute !important;
            inset: 0 !important;
            top: 0 !important;
            left: 0 !important;
            display: block !important;
            background: transparent !important;
            z-index: 1 !important;
        }
        /* Hide html5-qrcode's own canvas/shading/paused overlay without hiding the video wrapper. */
        #scanner-region > canvas,
        #scanner-region > img,
        #scanner-region > div[style*="position: absolute"] {
            display: none !important;
        }
    </style>

    <main class="mx-auto max-w-3xl px-4 pb-32 pt-4">
        {{-- Camera viewfinder --}}
        <div class="relative overflow-hidden rounded-2xl bg-black aspect-[3/4] sm:aspect-[4/3]">
            <div id="scanner-region" class="absolute inset-0"></div>

            <div x-show="cameraOn" x-cloak class="pointer-events-none absolute inset-0 flex items-center justify-center">
                <div class="relative h-64 w-64 max-h-[60%] max-w-[80%]">
                    <span class="absolute -top-0.5 -left-0.5 h-8 w-8 border-t-4 border-l-4 border-brand-400 rounded-tl-lg"></span>
                    <span class="absolute -top-0.5 -right-0.5 h-8 w-8 border-t-4 border-r-4 border-brand-400 rounded-tr-lg"></span>
                    <span class="absolute -bottom-0.5 -left-0.5 h-8 w-8 border-b-4 border-l-4 border-brand-400 rounded-bl-lg"></span>
                    <span class="absolute -bottom-0.5 -right-0.5 h-8 w-8 border-b-4 border-r-4 border-brand-400 rounded-br-lg"></span>
                </div>
            </div>

            <div x-show="!cameraOn" class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-3 p-6 text-center">
                <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-slate-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/>
                    <circle cx="12" cy="13" r="4"/>
                </svg>
                <p class="text-sm text-slate-400" x-show="!cameraError">{{ __('app.scan.camera_off_hint') }}</p>

                <template x-if="cameraError">
                    <div class="max-w-xs rounded-lg bg-rose-500/15 px-4 py-3 text-left text-xs text-rose-200 ring-1 ring-rose-500/40">
                        <div class="font-semibold text-rose-100">{{ __('app.scan.camera_failed') }}</div>
                        <div class="mt-1 break-words" x-text="cameraError"></div>
                        <ul class="mt-2 list-inside list-disc space-y-0.5 text-rose-200/80">
                            <li>{{ __('app.scan.tip_https') }}</li>
                            <li>{{ __('app.scan.tip_permission') }}</li>
                            <li>{{ __('app.scan.tip_inapp') }}</li>
                        </ul>
                    </div>
                </template>

                <button type="button" @click="startCamera()" :disabled="starting"
                        class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg hover:bg-brand-700 disabled:opacity-60">
                    <span x-show="!starting">{{ __('app.scan.start_camera') }}</span>
                    <span x-show="starting" x-cloak>{{ __('app.scan.starting') }}…</span>
                </button>
            </div>

            <div x-show="cameraOn" class="absolute right-3 top-3 z-30 flex items-center gap-2">
                <button x-show="cameras.length > 1" @click="switchCamera()" type="button"
                        class="rounded-full bg-black/50 p-2 text-white backdrop-blur hover:bg-black/70"
                        aria-label="{{ __('app.scan.switch_camera') }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
                <button @click="stopCamera()" type="button"
                        class="rounded-full bg-black/50 p-2 text-white backdrop-blur hover:bg-black/70"
                        aria-label="{{ __('app.scan.stop_camera') }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Custom-score modal --}}
        @if ($askScore)
            <div class="fixed inset-0 z-30 flex items-end justify-center bg-black/70 backdrop-blur-sm sm:items-center"
                 x-data x-init="$nextTick(() => $refs.scoreInput && $refs.scoreInput.focus())"
                 wire:key="score-prompt">
                <form wire:submit.prevent="saveScore"
                      class="w-full max-w-md rounded-t-2xl bg-slate-800 p-6 ring-1 ring-slate-700 sm:rounded-2xl">
                    <div class="text-xs uppercase tracking-wider text-brand-300">{{ __('app.scan.enter_score') }}</div>
                    <div class="mt-1 text-xl font-bold">
                        @if ($pendingStudentNumber) <span class="text-slate-400">#{{ $pendingStudentNumber }}</span> @endif
                        {{ $pendingStudentName }}
                    </div>
                    <div class="mt-4">
                        <input wire:model="pendingScore" x-ref="scoreInput"
                               type="number" min="0" max="100" step="1" inputmode="numeric"
                               class="w-full rounded-lg border-slate-600 bg-slate-900 px-4 py-3 text-center text-3xl font-bold text-white focus:border-brand-500 focus:ring-brand-500"
                               placeholder="0–100">
                        @if ($scoreError)
                            <p class="mt-2 text-xs text-rose-400">{{ $scoreError }}</p>
                        @endif
                    </div>
                    <div class="mt-5 flex gap-2">
                        <button type="button" wire:click="cancelScore"
                                class="flex-1 rounded-lg border border-slate-600 px-4 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-700">
                            {{ __('app.common.cancel') }}
                        </button>
                        <button type="submit"
                                class="flex-[2] rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                            {{ __('app.scan.save_score') }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Last scan feedback --}}
        @if ($message)
            <div wire:key="msg-{{ $lastStudentId }}-{{ md5($message) }}"
                 @class([
                    'mt-4 rounded-xl p-4 text-center',
                    'bg-emerald-500/20 ring-1 ring-emerald-400' => $messageType === 'success',
                    'bg-amber-500/20 ring-1 ring-amber-400' => $messageType === 'duplicate',
                    'bg-rose-500/20 ring-1 ring-rose-400' => $messageType === 'error',
                    'bg-sky-500/20 ring-1 ring-sky-400' => $messageType === 'undo',
                 ])
                 x-data x-init="
                    if (window.navigator.vibrate) navigator.vibrate({{ $messageType === 'success' ? 80 : ($messageType === 'duplicate' ? 30 : 200) }});
                    if ('{{ $messageType }}' === 'success') window.dispatchEvent(new CustomEvent('scan-beep'));
                 ">
                @if ($messageType === 'success')
                    <div class="flex items-center justify-center gap-2 text-sm text-emerald-300">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        {{ __('app.scan.success') }}
                        @if ($lastScore !== null)
                            <span class="ml-2 rounded-full bg-emerald-400/30 px-2 py-0.5 text-xs font-bold text-emerald-100">{{ $lastScore }}</span>
                        @endif
                    </div>
                    <div class="mt-1 text-xl font-bold text-white">
                        @if ($lastStudentNumber) <span class="text-emerald-300">#{{ $lastStudentNumber }}</span> @endif
                        {{ $lastStudentName }}
                    </div>
                    @if ($lastStudentId)
                        <button type="button" wire:click="undo({{ $lastStudentId }})"
                                class="mt-2 text-xs text-emerald-200 underline-offset-2 hover:underline">
                            {{ __('app.scan.undo') }}
                        </button>
                    @endif
                @elseif ($messageType === 'duplicate')
                    <div class="text-sm text-amber-300">{{ __('app.scan.duplicate') }}</div>
                    <div class="mt-1 text-lg font-semibold text-white">
                        @if ($lastStudentNumber) #{{ $lastStudentNumber }} @endif {{ $lastStudentName }}
                    </div>
                    @if ($lastStudentId)
                        <button type="button" wire:click="undo({{ $lastStudentId }})"
                                class="mt-2 text-xs text-amber-200 underline-offset-2 hover:underline">
                            {{ __('app.scan.undo') }}
                        </button>
                    @endif
                @elseif ($messageType === 'undo')
                    <div class="text-sm text-sky-300">↶ {{ $message }}</div>
                @else
                    <div class="flex items-center justify-center gap-2 text-rose-300">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                        <span class="text-sm font-medium">{{ $message }}</span>
                    </div>
                @endif
            </div>
        @endif

        {{-- Manual input --}}
        <form wire:submit.prevent="scan" class="mt-4">
            <label class="block text-xs uppercase tracking-wider text-slate-400">{{ __('app.scan.manual_input') }}</label>
            <div class="mt-1.5 flex gap-2">
                <input wire:model="code" type="text" autofocus
                       x-ref="codeInput"
                       autocomplete="off" inputmode="text" autocapitalize="characters"
                       placeholder="{{ __('app.scan.code_placeholder') }}"
                       class="flex-1 rounded-lg border-slate-600 bg-slate-800 px-4 py-3 font-mono uppercase text-white placeholder:text-slate-500 focus:border-brand-500 focus:ring-brand-500">
                <button type="submit"
                        class="rounded-lg bg-brand-600 px-5 py-3 text-sm font-semibold text-white hover:bg-brand-700">
                    {{ __('app.scan.submit') }}
                </button>
            </div>
        </form>

        {{-- Recent submissions (with undo) --}}
        @if ($submitted->isNotEmpty())
            <div class="mt-8">
                <h3 class="mb-3 text-sm font-semibold text-slate-300">
                    {{ __('app.scan.recent_submitted') }}
                    <span class="ml-1 text-slate-500">({{ $submitted->count() }})</span>
                </h3>
                <div class="space-y-1.5">
                    @foreach ($submitted->take(8) as $sub)
                        <div wire:key="sub-{{ $sub->id }}"
                             class="flex items-center justify-between rounded-lg border border-slate-700 bg-slate-800/50 px-3 py-2 text-sm">
                            <div class="flex items-center gap-2">
                                @if ($sub->student?->number)
                                    <span class="text-slate-400">#{{ $sub->student->number }}</span>
                                @endif
                                <span class="text-slate-100">{{ $sub->student?->name }}</span>
                                @if ($sub->score !== null)
                                    <span class="ml-1 rounded-full bg-emerald-500/20 px-2 py-0.5 text-xs font-bold text-emerald-300">{{ $sub->score }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-slate-500">{{ $sub->submitted_at?->format('H:i') }}</span>
                                <button type="button" wire:click="undo({{ $sub->student_id }})"
                                        wire:confirm="{{ __('app.scan.undo_confirm') }}"
                                        class="rounded p-1 text-slate-400 hover:bg-slate-700 hover:text-rose-300">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Not submitted list --}}
        <div class="mt-8">
            <h3 class="mb-3 text-sm font-semibold text-slate-300">
                {{ __('app.scan.not_yet') }}
                <span class="ml-1 text-slate-500">({{ $notSubmitted->count() }})</span>
            </h3>
            @if ($notSubmitted->isEmpty())
                <div class="rounded-xl bg-emerald-500/10 p-4 text-center text-sm text-emerald-300">
                    {{ __('app.scan.all_done') }}
                </div>
            @else
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach ($notSubmitted as $student)
                        <div class="rounded-lg border border-slate-700 bg-slate-800/50 px-3 py-2 text-sm">
                            @if ($student->number)
                                <span class="text-slate-400">#{{ $student->number }}</span>
                            @endif
                            <span class="text-slate-100">{{ $student->name }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </main>

    @script
    <script>
        Alpine.data('scanDashboard', () => ({
            cameraOn: false,
            starting: false,
            scanner: null,
            lastScanAt: 0,
            audioCtx: null,
            cameras: [],
            currentCameraIdx: 0,
            cameraError: null,

            onScan(decodedText) {
                const now = Date.now();
                if (now - this.lastScanAt < 1500) return;
                this.lastScanAt = now;
                this.$wire.set('code', decodedText, false);
                this.$wire.scan();
            },

            async startCamera() {
                if (this.starting) return;
                this.cameraError = null;
                this.starting = true;
                try {
                    if (!window.Html5Qrcode) {
                        throw new Error('Scanner library not loaded — please reload the page.');
                    }
                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                        throw new Error('This browser does not support camera access. Try the default browser (Chrome / Safari) instead of an in-app browser.');
                    }
                    if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                        throw new Error('Camera requires HTTPS. Current page is loaded over ' + window.location.protocol);
                    }

                    // Probe permission with a throwaway stream so we surface
                    // a clean error message instead of a cryptic getUserMedia failure later.
                    try {
                        const probe = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } } });
                        probe.getTracks().forEach((t) => t.stop());
                    } catch (e) {
                        const msg = (e && e.name === 'NotAllowedError')
                            ? 'Camera permission was denied. Allow camera access for this site and tap Start camera again.'
                            : (e && e.name === 'NotFoundError')
                                ? 'No camera was found on this device.'
                                : (e && e.message) || String(e);
                        throw new Error(msg);
                    }

                    if (this.cameras.length === 0) {
                        try {
                            this.cameras = await Html5Qrcode.getCameras();
                        } catch (e) {
                            this.cameras = [];
                        }
                    }

                    this.scanner = new Html5Qrcode('scanner-region', { verbose: false });

                    const callbacks = (decoded) => this.onScan(decoded);
                    const config = { fps: 10, disableFlip: false, aspectRatio: 1.0 };

                    let started = false;
                    if (this.cameras.length > 0) {
                        // Prefer back camera by label keyword; otherwise the first one.
                        let preferred = this.cameras.findIndex((c) => /back|rear|environment/i.test(c.label));
                        if (preferred < 0) preferred = this.currentCameraIdx % this.cameras.length;
                        this.currentCameraIdx = preferred;
                        try {
                            await this.scanner.start(this.cameras[preferred].id, config, callbacks, () => {});
                            started = true;
                        } catch (e) {
                            // Fall through to facingMode attempt.
                        }
                    }
                    if (!started) {
                        await this.scanner.start({ facingMode: { ideal: 'environment' } }, config, callbacks, () => {});
                    }

                    this.cameraOn = true;
                } catch (e) {
                    this.cameraError = (e && e.message) ? e.message : String(e);
                    if (this.scanner) {
                        try { await this.scanner.stop(); } catch (_) {}
                        try { this.scanner.clear(); } catch (_) {}
                        this.scanner = null;
                    }
                    this.cameraOn = false;
                } finally {
                    this.starting = false;
                }
            },

            async stopCamera() {
                if (this.scanner) {
                    try { await this.scanner.stop(); } catch (e) {}
                    try { this.scanner.clear(); } catch (e) {}
                    this.scanner = null;
                }
                this.cameraOn = false;
                this.cameraError = null;
            },

            async switchCamera() {
                if (this.cameras.length < 2) return;
                this.currentCameraIdx = (this.currentCameraIdx + 1) % this.cameras.length;
                await this.stopCamera();
                this.scanner = new Html5Qrcode('scanner-region', { verbose: false });
                try {
                    await this.scanner.start(
                        this.cameras[this.currentCameraIdx].id,
                        { fps: 10, disableFlip: false, aspectRatio: 1.0 },
                        (d) => this.onScan(d),
                        () => {}
                    );
                    this.cameraOn = true;
                } catch (e) {
                    this.cameraError = (e && e.message) ? e.message : String(e);
                }
            },

            beep() {
                try {
                    if (!this.audioCtx) {
                        const AC = window.AudioContext || window.webkitAudioContext;
                        if (!AC) return;
                        this.audioCtx = new AC();
                    }
                    const ctx = this.audioCtx;
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.type = 'sine';
                    osc.frequency.value = 1320;
                    gain.gain.setValueAtTime(0.15, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.12);
                } catch (e) {}
            },

            init() {
                this.$nextTick(() => this.$refs.codeInput?.focus());
                document.addEventListener('livewire:navigated', () => this.$refs.codeInput?.focus());
                window.addEventListener('scan-beep', () => this.beep());
            },
        }));
    </script>
    @endscript
</div>
