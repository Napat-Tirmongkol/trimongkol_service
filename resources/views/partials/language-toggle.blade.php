@php $locale = app()->getLocale(); @endphp
<div class="inline-flex items-center overflow-hidden rounded-md border border-slate-200 text-xs font-semibold">
    <a href="{{ route('locale.switch', 'th') }}"
       class="px-3 py-1.5 transition {{ $locale === 'th' ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50' }}">TH</a>
    <a href="{{ route('locale.switch', 'en') }}"
       class="px-3 py-1.5 transition {{ $locale === 'en' ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50' }}">EN</a>
</div>
