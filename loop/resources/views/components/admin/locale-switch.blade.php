@php
    $here = url()->full();
    $locale = app()->getLocale();
@endphp
<div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-0.5 text-xs font-semibold" role="group" aria-label="{{ __('loop.language') }}">
    <a
        href="{{ route('locale', ['locale' => 'en', 'return' => $here]) }}"
        class="rounded-md px-2.5 py-1 {{ $locale === 'en' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:text-slate-800' }}"
    >EN</a>
    <a
        href="{{ route('locale', ['locale' => 'sw', 'return' => $here]) }}"
        class="rounded-md px-2.5 py-1 {{ $locale === 'sw' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:text-slate-800' }}"
    >SW</a>
</div>
