@extends('layouts.marketing')

@section('title', t('ocr.heading') . ' — ' . t('brand.name'))
@section('description', t('ocr.subheading'))

@php
    // Strings the Alpine/JS side needs — kept in one place so TH/EN stay paired.
    $i18n = [
        'err_not_image' => t('ocr.err_not_image'),
        'err_no_clipboard_image' => t('ocr.err_no_clipboard_image'),
        'err_failed' => t('ocr.err_failed'),
        'empty_result' => t('ocr.empty_result'),
        'copied' => t('ocr.copied'),
        'status_loading_lang' => t('ocr.status_loading_lang'),
        'status_initializing' => t('ocr.status_initializing'),
        'status_recognizing' => t('ocr.status_recognizing'),
    ];
    $badges = [
        ['icon' => 'gift', 'text' => t('ocr.badge_free')],
        ['icon' => 'shield', 'text' => t('ocr.badge_private')],
        ['icon' => 'languages', 'text' => t('ocr.badge_langs')],
    ];
    $steps = [
        ['title' => t('ocr.step1_title'), 'text' => t('ocr.step1_text')],
        ['title' => t('ocr.step2_title'), 'text' => t('ocr.step2_text')],
        ['title' => t('ocr.step3_title'), 'text' => t('ocr.step3_text')],
    ];
    $tips = [t('ocr.tip1'), t('ocr.tip2'), t('ocr.tip3'), t('ocr.tip4')];
@endphp

@section('content')
    {{-- Hero (gradient, no image asset needed) --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-brand-700 via-brand-800 to-brand-950 pt-28 pb-36 text-white">
        <div class="bg-grid absolute inset-0 opacity-10" aria-hidden="true"></div>
        <div class="relative mx-auto max-w-3xl px-4 text-center sm:px-6">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-white/30 bg-white/10 px-4 py-1 text-xs font-medium uppercase tracking-[0.18em] text-white/90 backdrop-blur">
                {{ t('ocr.eyebrow') }}
            </span>
            <h1 class="mt-6 text-4xl font-extrabold leading-[1.1] tracking-tight drop-shadow-md md:text-6xl">
                {{ t('ocr.heading') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-base text-white/85 md:text-lg">{{ t('ocr.subheading') }}</p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-2">
                @foreach ($badges as $badge)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-white/90 ring-1 ring-white/20 backdrop-blur">
                        @switch($badge['icon'])
                            @case('gift')
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
                                @break
                            @case('shield')
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                @break
                            @default
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 8 6 6"/><path d="m4 14 6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/><path d="m22 22-5-10-5 10"/><path d="M14 18h6"/></svg>
                        @endswitch
                        {{ $badge['text'] }}
                    </span>
                @endforeach
            </div>
        </div>
    </section>

    {{-- The tool — overlaps the hero like the other marketing pages --}}
    <section class="relative z-20 mx-auto -mt-24 max-w-6xl px-4 sm:px-6">
        <div x-data="ocrTool()"
             @dragover.prevent="dragging = true"
             @dragleave.prevent="dragging = false"
             @drop.prevent="onDrop($event)"
             class="rounded-3xl bg-white p-5 shadow-2xl shadow-slate-900/10 ring-1 ring-slate-200/50 sm:p-8">

            {{-- Hidden inputs (kept outside the toggles so refs persist) --}}
            <input type="file" accept="image/*" x-ref="file" class="hidden" @change="onFileChange($event)">
            <input type="file" accept="image/*" capture="environment" x-ref="camera" class="hidden" @change="onFileChange($event)">

            <div class="grid gap-6 lg:grid-cols-2">
                {{-- ── Input column ────────────────────────────────────── --}}
                <div class="flex flex-col">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-500">{{ t('ocr.input_title') }}</h2>

                        {{-- Language selector --}}
                        <div class="inline-flex rounded-full bg-slate-100 p-1" role="group" aria-label="{{ t('ocr.lang_label') }}">
                            @php
                                $langs = [
                                    'tha+eng' => t('ocr.lang_both'),
                                    'tha' => t('ocr.lang_th'),
                                    'eng' => t('ocr.lang_en'),
                                ];
                            @endphp
                            @foreach ($langs as $code => $label)
                                <button type="button" @click="lang = '{{ $code }}'"
                                        :class="lang === '{{ $code }}' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                        class="rounded-full px-3 py-1 text-xs font-semibold transition">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Dropzone (no image yet) --}}
                    <button type="button" x-show="!hasImage" @click="pickFile()"
                            :class="dragging ? 'border-brand-500 bg-brand-50/60' : 'border-slate-300 hover:border-brand-400 hover:bg-slate-50'"
                            class="mt-3 flex min-h-[16rem] flex-1 flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed px-6 py-10 text-center transition">
                        <span class="grid h-14 w-14 place-items-center rounded-2xl bg-brand-50 text-brand-600">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                        </span>
                        <span class="text-base font-semibold text-slate-800">{{ t('ocr.drop_title') }}</span>
                        <span class="max-w-xs text-xs leading-relaxed text-slate-500">{{ t('ocr.drop_hint') }}</span>
                    </button>

                    {{-- Preview (image loaded) --}}
                    <div x-show="hasImage" x-cloak class="mt-3 flex-1">
                        <div class="relative overflow-hidden rounded-2xl bg-slate-900/95 ring-1 ring-slate-200">
                            <img :src="image" alt="" class="mx-auto max-h-[20rem] w-full object-contain">
                            <button type="button" @click="clearImage()"
                                    class="absolute right-2 top-2 inline-flex items-center gap-1 rounded-full bg-slate-900/70 px-3 py-1.5 text-xs font-medium text-white backdrop-blur transition hover:bg-slate-900">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                {{ t('ocr.change_image') }}
                            </button>
                        </div>
                    </div>

                    {{-- Input source buttons --}}
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" @click="pickFile()"
                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            {{ t('ocr.choose_file') }}
                        </button>
                        <button type="button" @click="$refs.camera.click()"
                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                            {{ t('ocr.use_camera') }}
                        </button>
                        <button type="button" @click="pasteFromClipboard()"
                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>
                            {{ t('ocr.paste_btn') }}
                        </button>
                    </div>

                    {{-- Recognize --}}
                    <button type="button" @click="recognize()" :disabled="busy || !hasImage"
                            class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-full bg-brand-600 px-6 py-3.5 text-base font-semibold text-white shadow-lg shadow-brand-600/30 transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60">
                        <template x-if="!busy">
                            <span class="inline-flex items-center gap-2">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V5a1 1 0 0 1 1-1h2"/><path d="M17 4h2a1 1 0 0 1 1 1v2"/><path d="M20 17v2a1 1 0 0 1-1 1h-2"/><path d="M7 20H5a1 1 0 0 1-1-1v-2"/><path d="M7 8h10"/><path d="M7 12h10"/><path d="M7 16h6"/></svg>
                                {{ t('ocr.recognize') }}
                            </span>
                        </template>
                        <template x-if="busy">
                            <span class="inline-flex items-center gap-2">
                                <svg class="animate-spin" width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                                {{ t('ocr.recognizing') }}
                            </span>
                        </template>
                    </button>

                    {{-- Progress --}}
                    <div x-show="busy" x-cloak class="mt-3">
                        <div class="flex items-center justify-between text-xs text-slate-500">
                            <span x-text="statusText"></span>
                            <span x-text="progress + '%'"></span>
                        </div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-brand-600 transition-all duration-300" :style="`width: ${progress}%`"></div>
                        </div>
                    </div>
                </div>

                {{-- ── Output column ───────────────────────────────────── --}}
                <div class="flex flex-col">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-500">{{ t('ocr.result_title') }}</h2>
                        <span class="text-xs text-slate-400" x-show="text.length > 0" x-cloak>
                            <span x-text="charCount"></span> {{ t('ocr.chars') }} · <span x-text="wordCount"></span> {{ t('ocr.words') }}
                        </span>
                    </div>

                    <textarea x-ref="output" x-model="text" dir="auto" spellcheck="false"
                              placeholder="{{ t('ocr.result_placeholder') }}"
                              class="mt-3 min-h-[20rem] flex-1 resize-y rounded-2xl border border-slate-200 bg-slate-50/60 p-4 text-sm leading-relaxed text-slate-800 shadow-inner focus:border-brand-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-200"></textarea>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" @click="copyText()" :disabled="!text"
                                class="inline-flex items-center gap-1.5 rounded-full bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            {{ t('ocr.copy') }}
                        </button>
                        <button type="button" @click="download()" :disabled="!text"
                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            {{ t('ocr.download') }}
                        </button>
                        <button type="button" @click="clearText()" :disabled="!text"
                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            {{ t('ocr.clear') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Privacy reassurance --}}
            <div class="mt-6 flex items-start gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-xs leading-relaxed text-emerald-800 ring-1 ring-emerald-200">
                <svg class="mt-0.5 shrink-0" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                {{ t('ocr.privacy_note') }}
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="mx-auto mt-20 max-w-6xl px-4 sm:px-6 md:mt-28">
        <h2 class="text-center text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">{{ t('ocr.how_title') }}</h2>
        <div class="mt-10 grid gap-4 md:grid-cols-3">
            @foreach ($steps as $i => $step)
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-500/20">
                        <span class="text-sm font-bold">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $step['text'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Tips --}}
    <section class="mx-auto mt-16 max-w-6xl px-4 pb-8 sm:px-6 md:mt-20">
        <div class="rounded-3xl bg-slate-900 p-8 text-white shadow-xl md:p-10">
            <h2 class="text-xl font-bold tracking-tight md:text-2xl">{{ t('ocr.tips_title') }}</h2>
            <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                @foreach ($tips as $tip)
                    <li class="flex items-start gap-3 rounded-2xl bg-white/5 p-4 ring-1 ring-white/10">
                        <span class="grid h-6 w-6 flex-shrink-0 place-items-center rounded-full bg-brand-500 text-white">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                        <span class="text-sm text-slate-200">{{ $tip }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Scripts: i18n bridge + Tesseract.js (CDN, client-side) + Alpine component --}}
    <script>
        window.__ocrI18n = @json($i18n);
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('ocrTool', () => ({
                hasImage: false,
                image: null,        // data URL for the <img> preview
                busy: false,
                dragging: false,
                progress: 0,
                statusText: '',
                text: '',
                lang: 'tha+eng',
                _worker: null,
                _workerLang: null,
                i18n: window.__ocrI18n || {},

                init() {
                    this._onPaste = (e) => this.handlePaste(e);
                    window.addEventListener('paste', this._onPaste);
                },
                destroy() {
                    window.removeEventListener('paste', this._onPaste);
                    if (this._worker) { this._worker.terminate(); this._worker = null; }
                },

                get charCount() { return this.text.length; },
                get wordCount() {
                    const t = this.text.trim();
                    return t ? t.split(/\s+/).filter(Boolean).length : 0;
                },

                pickFile() { this.$refs.file.click(); },

                onFileChange(e) {
                    const f = e.target.files && e.target.files[0];
                    if (f) this.loadFile(f);
                    e.target.value = '';   // allow re-picking the same file
                },

                handlePaste(e) {
                    const items = (e.clipboardData && e.clipboardData.items) || [];
                    for (const it of items) {
                        if (it.type && it.type.startsWith('image/')) {
                            const f = it.getAsFile();
                            if (f) { this.loadFile(f); e.preventDefault(); return; }
                        }
                    }
                    // No image in clipboard → let normal text paste happen.
                },

                async pasteFromClipboard() {
                    try {
                        const items = await navigator.clipboard.read();
                        for (const item of items) {
                            const type = item.types.find((t) => t.startsWith('image/'));
                            if (type) {
                                const blob = await item.getType(type);
                                this.loadFile(new File([blob], 'clipboard.png', { type }));
                                return;
                            }
                        }
                        window.flashToast({ message: this.i18n.err_no_clipboard_image, type: 'info' });
                    } catch (err) {
                        window.flashToast({ message: this.i18n.err_no_clipboard_image, type: 'info' });
                    }
                },

                onDrop(e) {
                    this.dragging = false;
                    const f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
                    if (f) this.loadFile(f);
                },

                loadFile(file) {
                    if (!file.type || !file.type.startsWith('image/')) {
                        window.flashToast({ message: this.i18n.err_not_image, type: 'error' });
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        this.image = ev.target.result;
                        this.hasImage = true;
                        this.text = '';
                        this.progress = 0;
                        this.statusText = '';
                    };
                    reader.readAsDataURL(file);
                },

                clearImage() {
                    this.hasImage = false;
                    this.image = null;
                    this.text = '';
                    this.progress = 0;
                    this.statusText = '';
                },

                clearText() {
                    this.text = '';
                    this.progress = 0;
                },

                async ensureTesseract() {
                    for (let i = 0; i < 200 && !window.Tesseract; i++) {
                        await new Promise((r) => setTimeout(r, 50));
                    }
                    return window.Tesseract;
                },

                statusFor(s) {
                    if (!s) return this.i18n.status_initializing;
                    if (s.includes('language')) return this.i18n.status_loading_lang;
                    if (s.includes('recognizing')) return this.i18n.status_recognizing;
                    return this.i18n.status_initializing;
                },

                async recognize() {
                    if (!this.hasImage || this.busy) return;
                    const Tesseract = await this.ensureTesseract();
                    if (!Tesseract) {
                        window.flashToast({ message: this.i18n.err_failed, type: 'error' });
                        return;
                    }

                    this.busy = true;
                    this.progress = 0;
                    this.statusText = this.i18n.status_initializing;
                    this.text = '';

                    try {
                        // Reuse the worker unless the language changed — avoids
                        // re-downloading the (cached) trained data each run.
                        if (!this._worker || this._workerLang !== this.lang) {
                            if (this._worker) { await this._worker.terminate(); this._worker = null; }
                            this._worker = await Tesseract.createWorker(this.lang, 1, {
                                logger: (m) => {
                                    this.statusText = this.statusFor(m.status);
                                    if (typeof m.progress === 'number') {
                                        this.progress = Math.round(m.progress * 100);
                                    }
                                },
                            });
                            this._workerLang = this.lang;
                        }

                        const { data } = await this._worker.recognize(this.image);
                        const out = (data && data.text ? data.text : '').replace(/\n{3,}/g, '\n\n').trim();
                        this.text = out;
                        this.progress = 100;
                        if (!out) {
                            window.flashToast({ message: this.i18n.empty_result, type: 'info' });
                        }
                    } catch (err) {
                        console.error('OCR failed:', err);
                        window.flashToast({ message: this.i18n.err_failed, type: 'error' });
                    } finally {
                        this.busy = false;
                    }
                },

                async copyText() {
                    if (!this.text) return;
                    try {
                        await navigator.clipboard.writeText(this.text);
                    } catch (err) {
                        // Fallback for non-secure contexts / older browsers.
                        const ta = this.$refs.output;
                        ta.focus();
                        ta.select();
                        document.execCommand('copy');
                        window.getSelection()?.removeAllRanges();
                    }
                    window.flashToast({ message: this.i18n.copied, type: 'success' });
                },

                download() {
                    if (!this.text) return;
                    const blob = new Blob([this.text], { type: 'text/plain;charset=utf-8' });
                    const a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    a.download = 'ocr-text.txt';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(a.href);
                },
            }));
        });
    </script>
@endsection
