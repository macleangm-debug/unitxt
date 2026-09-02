@props(['href'])

<a
    href="{{ $href }}"
    {{ $attributes->merge([
        'class' => 'inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-ink/10 bg-white text-ink shadow-sm hover:border-ink/20',
        'aria-label' => __('loop.back'),
        'title' => __('loop.back'),
    ]) }}
>
    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
</a>
