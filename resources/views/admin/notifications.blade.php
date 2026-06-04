<x-admin-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.nav') }}</div>
            <h2 class="mt-0.5 text-xl font-semibold leading-tight text-gray-800">{{ __('app.admin.notifications.heading') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.admin.notifications.subheading') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- LINE (Messaging API) --}}
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.notifications.line_heading') }}</h3>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('app.admin.notifications.line_desc') }}</p>
                </div>

                <form method="POST" action="{{ route('admin.notifications.line') }}" class="space-y-4 px-6 py-5">
                    @csrf
                    <label class="flex items-center gap-3">
                        <input type="hidden" name="line_enabled" value="0">
                        <input type="checkbox" name="line_enabled" value="1" @checked($lineEnabled)
                               class="h-5 w-5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span class="text-sm text-slate-700">{{ __('app.admin.notifications.line_enabled_label') }}</span>
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ __('app.admin.notifications.line_target') }}</label>
                            <input type="text" name="line_target" value="{{ $lineTarget }}" placeholder="Uxxxxxxxx… / Cxxxxxxxx…"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <p class="mt-1 text-xs text-slate-500">{{ __('app.admin.notifications.line_target_hint') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                {{ __('app.admin.notifications.line_token') }}
                                @if ($lineTokenSet)
                                    <span class="ml-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ __('app.admin.notifications.key_set') }}</span>
                                @endif
                            </label>
                            <input type="password" name="line_token" autocomplete="off"
                                   placeholder="{{ $lineTokenSet ? '••••••••  ('.__('app.admin.notifications.key_keep').')' : 'Channel access token' }}"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    </div>

                    <p class="text-xs text-slate-500">{{ __('app.admin.notifications.line_hint') }}</p>
                    <button type="submit" class="rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('app.admin.notifications.line_save') }}</button>
                </form>

                {{-- Captured LINE User IDs --}}
                <div class="border-t border-slate-100 bg-slate-50/50 px-6 py-5">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h4 class="text-sm font-semibold text-slate-900">ตรวจจับ User ID ล่าสุด (LINE User ID Catcher)</h4>
                            <p class="mt-0.5 text-xs text-slate-500">ใช้ตรวจสอบ LINE ID ของคุณสำหรับนำไปกรอกช่อง Target ID ด้านบน</p>
                        </div>
                        @php
                            $capturedList = json_decode(\App\Models\Setting::get('line.captured_users') ?: '[]', true);
                        @endphp
                        @if (!empty($capturedList))
                            <form method="POST" action="{{ route('admin.notifications.line-clear-captured') }}">
                                @csrf
                                <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-800 transition">
                                    ล้างประวัติการตรวจจับ
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white p-4">
                        <div class="mb-3 text-xs text-slate-600">
                            <span class="font-medium text-slate-700">วิธีใช้งาน:</span>
                            <ol class="list-decimal pl-4 mt-1 space-y-1">
                                <li>คัดลอก Webhook URL นี้: <code class="rounded bg-slate-100 px-1 py-0.5 font-mono text-[11px] text-slate-800">{{ url('/line/webhook') }}</code> ไปตั้งค่าที่หน้า **LINE Developers Console** (ในส่วน Messaging API settings > Webhook URL) และเปิดใช้งาน Webhook</li>
                                <li>แอดไลน์บอทของคุณเป็นเพื่อน จากนั้นทดลองพิมพ์ส่งข้อความคุยกับบอท (เช่น พิมพ์ว่า <code>ตรวจไอดี</code>)</li>
                                <li>หน้าเว็บนี้จะแสดงรายชื่อและ User ID ของคุณด้านล่าง สามารถกดปุ่มเพื่อคัดลอกหรือนำไปกรอกข้อมูลด้านบนได้ทันที</li>
                            </ol>
                        </div>

                        @if (empty($capturedList))
                            <div class="flex flex-col items-center justify-center py-6 text-center text-slate-400">
                                <svg class="h-8 w-8 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span class="text-xs">ยังไม่พบการเรียกใช้งาน Webhook/ข้อความที่ตรวจจับได้</span>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead>
                                        <tr class="border-b border-slate-100 text-slate-400 font-medium">
                                            <th class="py-2">ชื่อ LINE / ประเภท</th>
                                            <th class="py-2">User ID (หรือ Group/Room ID)</th>
                                            <th class="py-2">ข้อความล่าสุด</th>
                                            <th class="py-2 text-right">การจัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($capturedList as $cUser)
                                            <tr class="text-slate-700 hover:bg-slate-50/50">
                                                <td class="py-2.5 pr-2">
                                                    <span class="font-semibold text-slate-900">{{ $cUser['name'] }}</span>
                                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $cUser['type'] }}</div>
                                                </td>
                                                <td class="py-2.5 font-mono text-[11px] text-slate-600 select-all pr-2">
                                                    {{ $cUser['id'] }}
                                                </td>
                                                <td class="py-2.5 text-slate-500 italic pr-2">
                                                    {{ $cUser['snippet'] ?: '— ไม่มีข้อความ (ติดตาม/แอดบอท) —' }}
                                                    <div class="text-[10px] text-slate-400 font-sans not-italic mt-0.5">{{ $cUser['timestamp'] }}</div>
                                                </td>
                                                <td class="py-2.5 text-right whitespace-nowrap">
                                                    <button type="button" 
                                                            onclick="navigator.clipboard.writeText('{{ $cUser['id'] }}'); alert('คัดลอก ID เรียบร้อยแล้ว!');"
                                                            class="rounded bg-slate-100 hover:bg-slate-200 px-2 py-1 text-[11px] font-semibold text-slate-700 transition mr-1">
                                                        คัดลอก ID
                                                    </button>
                                                    <button type="button" 
                                                            onclick="document.querySelector('input[name=&quot;line_target&quot;]').value = '{{ $cUser['id'] }}';"
                                                            class="rounded bg-brand-50 hover:bg-brand-100 px-2 py-1 text-[11px] font-semibold text-brand-700 transition">
                                                        เลือกใช้งาน
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 px-6 py-4">
                    <form method="POST" action="{{ route('admin.notifications.line-test') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                            {{ __('app.admin.notifications.line_test') }}
                        </button>
                    </form>
                    <span class="text-xs text-slate-400">{{ __('app.admin.notifications.line_test_hint') }}</span>
                </div>
            </div>

            {{-- Discord (incoming webhook) --}}
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.notifications.discord_heading') }}</h3>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('app.admin.notifications.discord_desc') }}</p>
                </div>

                <form method="POST" action="{{ route('admin.notifications.discord') }}" class="space-y-4 px-6 py-5">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.admin.notifications.discord_webhook') }}</label>
                        <input type="url" name="discord_webhook" value="{{ $discordWebhook }}"
                               placeholder="https://discord.com/api/webhooks/…"
                               class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <p class="mt-1 text-xs text-slate-500">{{ __('app.admin.notifications.discord_hint') }}</p>
                    </div>
                    <button type="submit" class="rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('app.admin.notifications.discord_save') }}</button>
                </form>

                <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 px-6 py-4">
                    <form method="POST" action="{{ route('admin.notifications.discord-test') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            {{ __('app.admin.notifications.discord_test') }}
                        </button>
                    </form>
                    <span class="text-xs text-slate-400">{{ __('app.admin.notifications.discord_test_hint') }}</span>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
