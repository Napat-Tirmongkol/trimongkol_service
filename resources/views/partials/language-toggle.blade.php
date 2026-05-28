@php $locale = app()->getLocale(); @endphp
<div class="inline-flex items-center gap-0.5 rounded-full bg-slate-100/80 p-1 text-xs font-semibold backdrop-blur">
    <a href="{{ route('locale.switch', 'th') }}"
       class="rounded-full px-3 py-1 transition {{ $locale === 'th' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">TH</a>
    <a href="{{ route('locale.switch', 'en') }}"
       class="rounded-full px-3 py-1 transition {{ $locale === 'en' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">EN</a>
</div>
