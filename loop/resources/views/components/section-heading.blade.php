@props([
    'title',
    'blurb' => null,
    'eyebrow' => null,
    'href' => null,
    'link' => null,
])

<div {{ $attributes->merge(['class' => 'loop-section-head']) }}>
    @if ($eyebrow)
        <p class="loop-section-eyebrow">{{ $eyebrow }}</p>
    @endif
    <div class="flex flex-wrap items-end justify-between gap-3">
        <h2 class="loop-section-title">{{ $title }}</h2>
        @if ($href && $link)
            <a href="{{ $href }}" class="loop-section-link shrink-0">{{ $link }}</a>
        @endif
    </div>
    @if ($blurb)
        <p class="loop-section-blurb">{{ $blurb }}</p>
    @endif
</div>
