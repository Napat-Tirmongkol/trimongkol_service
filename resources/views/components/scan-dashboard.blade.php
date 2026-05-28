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
            $this->message = __('app.scan.not_found', ['code' => $code]);
            $this->messageType = 'error';
            $this->lastStudentId = null;
            $this->lastStudentName = null;
            $this->lastStudentNumber = null;
            return;
        }

        $submission = Submission::firstOrCreate(
            [
                'assignment_id' => $this->assignment->id,
                'student_id' => $student->id,
            ],
            ['submitted_at' => now()]
        );

        if ($submission->wasRecentlyCreated) {
            $this->message = $student->name;
            $this->messageType = 'success';
        } else {
            $this->message = $student->name;
            $this->messageType = 'duplicate';
        }

        $this->lastStudentId = $student->id;
        $this->lastStudentName = $student->name;
        $this->lastStudentNumber = $student->number;
    }

    public function with(): array
    {
        $totalCount = $this->assignment->classroom->students()->count();
        $submittedIds = $this->assignment->submissions()->pluck('student_id')->all();
        $notSubmitted = $this->assignment->classroom->students()
            ->whereNotIn('id', $submittedIds)
            ->get();

        return [
            'totalCount' => $totalCount,
            'submittedCount' => count($submittedIds),
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
            </div>
            <div class="text-right">
                <div class="text-lg font-bold tabular-nums">{{ $submittedCount }} / {{ $totalCount }}</div>
                <div class="text-xs text-slate-400">{{ __('app.scan.submitted_label') }}</div>
            </div>
        </div>
    </header>

    <style>
        #scanner-region {
            background: #000;
        }
        #scanner-region video {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            display: block;
        }
        /* nuke every html5-qrcode injection except the video itself */
        #scanner-region > *:not(video) {
            display: none !important;
        }
    </style>

    <main class="mx-auto max-w-3xl px-4 pb-32 pt-4">
        {{-- Camera viewfinder (mobile-first portrait, landscape on tablet+) --}}
        <div class="relative overflow-hidden rounded-2xl bg-black aspect-[3/4] sm:aspect-[4/3]">
            <div id="scanner-region" class="absolute inset-0"></div>

            {{-- Custom scanning frame overlay --}}
            <div x-show="cameraOn" x-cloak class="pointer-events-none absolute inset-0 flex items-center justify-center">
                <div class="relative h-64 w-64 max-h-[60%] max-w-[80%]">
                    <span class="absolute -top-0.5 -left-0.5 h-8 w-8 border-t-4 border-l-4 border-brand-400 rounded-tl-lg"></span>
                    <span class="absolute -top-0.5 -right-0.5 h-8 w-8 border-t-4 border-r-4 border-brand-400 rounded-tr-lg"></span>
                    <span class="absolute -bottom-0.5 -left-0.5 h-8 w-8 border-b-4 border-l-4 border-brand-400 rounded-bl-lg"></span>
                    <span class="absolute -bottom-0.5 -right-0.5 h-8 w-8 border-b-4 border-r-4 border-brand-400 rounded-br-lg"></span>
                </div>
            </div>

            <div x-show="!cameraOn" class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-6 text-center">
                <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-slate-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/>
                    <circle cx="12" cy="13" r="4"/>
                </svg>
                <p class="text-sm text-slate-400">{{ __('app.scan.camera_off_hint') }}</p>
                <button type="button" @click="startCamera()"
                        class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg hover:bg-brand-700">
                    {{ __('app.scan.start_camera') }}
                </button>
            </div>

            <button x-show="cameraOn" @click="stopCamera()"
                    class="absolute right-3 top-3 z-10 rounded-full bg-black/50 p-2 text-white backdrop-blur hover:bg-black/70">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Last scan feedback --}}
        @if ($message)
            <div wire:key="msg-{{ $lastStudentId }}-{{ md5($message) }}"
                 @class([
                    'mt-4 rounded-xl p-4 text-center',
                    'bg-emerald-500/20 ring-1 ring-emerald-400' => $messageType === 'success',
                    'bg-amber-500/20 ring-1 ring-amber-400' => $messageType === 'duplicate',
                    'bg-rose-500/20 ring-1 ring-rose-400' => $messageType === 'error',
                 ])
                 x-data x-init="if (window.navigator.vibrate) navigator.vibrate({{ $messageType === 'success' ? 80 : ($messageType === 'duplicate' ? 30 : 200) }})">
                @if ($messageType === 'success')
                    <div class="flex items-center justify-center gap-2 text-sm text-emerald-300">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        {{ __('app.scan.success') }}
                    </div>
                    <div class="mt-1 text-xl font-bold text-white">
                        @if ($lastStudentNumber) <span class="text-emerald-300">#{{ $lastStudentNumber }}</span> @endif
                        {{ $lastStudentName }}
                    </div>
                @elseif ($messageType === 'duplicate')
                    <div class="text-sm text-amber-300">{{ __('app.scan.duplicate') }}</div>
                    <div class="mt-1 text-lg font-semibold text-white">
                        @if ($lastStudentNumber) #{{ $lastStudentNumber }} @endif {{ $lastStudentName }}
                    </div>
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

        {{-- Manual input (hardware scanner / typing) --}}
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
            scanner: null,

            async startCamera() {
                if (! window.Html5Qrcode) {
                    alert('Scanner library not loaded');
                    return;
                }
                try {
                    this.scanner = new Html5Qrcode('scanner-region', { verbose: false });
                    await this.scanner.start(
                        { facingMode: 'environment' },
                        { fps: 10, disableFlip: false },
                        (decodedText) => {
                            this.$wire.set('code', decodedText, false);
                            this.$wire.scan();
                        },
                        () => {}
                    );
                    this.cameraOn = true;
                } catch (e) {
                    alert('Camera error: ' + e.message);
                }
            },

            async stopCamera() {
                if (this.scanner) {
                    try { await this.scanner.stop(); } catch (e) {}
                    this.scanner.clear();
                    this.scanner = null;
                }
                this.cameraOn = false;
            },

            init() {
                this.$nextTick(() => this.$refs.codeInput?.focus());
                document.addEventListener('livewire:navigated', () => this.$refs.codeInput?.focus());
            },
        }));
    </script>
    @endscript
</div>
