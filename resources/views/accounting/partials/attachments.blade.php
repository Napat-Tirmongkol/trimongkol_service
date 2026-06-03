{{--
    Variables:
      $attachments   — collection of Attachment models
      $uploadRoute   — named route for uploading (accounting.attachments.store)
      $uploadParams  — ['type' => 'invoice'|'bill', 'id' => $id]
      $canDelete     — bool
--}}
<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-3">
        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.attachments_heading') }}</h3>
        <form method="POST"
              action="{{ route($uploadRoute, $uploadParams) }}"
              enctype="multipart/form-data"
              class="flex items-center gap-2">
            @csrf
            <label class="cursor-pointer">
                <span class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
                    {{ __('app.accounting.attachment_upload_btn') }}
                </span>
                <input type="file"
                       name="file"
                       accept=".pdf,.jpg,.jpeg,.png,.webp"
                       class="sr-only"
                       onchange="this.closest('form').submit()">
            </label>
        </form>
    </div>

    @if ($attachments->isEmpty())
        <div class="px-6 py-6 text-center text-sm text-slate-400">
            {{ __('app.accounting.attachment_none') }}
        </div>
    @else
        <ul class="divide-y divide-slate-100">
            @foreach ($attachments as $attachment)
                <li class="flex items-center gap-3 px-6 py-3">
                    {{-- File type icon --}}
                    @php
                        $iconColor = match(true) {
                            str_contains($attachment->mime_type ?? '', 'pdf') => 'text-rose-500',
                            str_contains($attachment->mime_type ?? '', 'image') => 'text-sky-500',
                            default => 'text-slate-400',
                        };
                    @endphp
                    <svg class="h-5 w-5 shrink-0 {{ $iconColor }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>

                    <div class="min-w-0 flex-1">
                        <a href="{{ $attachment->url() }}"
                           class="truncate text-sm font-medium text-slate-800 hover:text-brand-700"
                           target="_blank" rel="noopener">{{ $attachment->original_name }}</a>
                        <div class="text-xs text-slate-400">
                            {{ $attachment->humanSize() }}
                            @if ($attachment->accountingUser)
                                · {{ $attachment->accountingUser->name }}
                            @endif
                            · {{ $attachment->created_at?->format('d/m/Y') }}
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <a href="{{ $attachment->url() }}"
                           class="text-xs font-medium text-brand-700 hover:text-brand-800"
                           target="_blank" rel="noopener">{{ __('app.accounting.view') }}</a>

                        @if ($canDelete)
                            <form method="POST"
                                  action="{{ route('accounting.attachments.destroy', $attachment) }}"
                                  data-confirm="{{ __('app.accounting.attachment_delete_confirm') }}"
                                  data-confirm-danger="1">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="text-xs font-medium text-rose-600 hover:text-rose-700">
                                    {{ __('app.accounting.delete') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
