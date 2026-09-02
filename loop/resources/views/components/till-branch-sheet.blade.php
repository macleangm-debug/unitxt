@props([
    'shops',
    'activeShop',
    'scanQuery' => null,
])

@php
    $shopCount = $shops->count();
    $showSearch = $shopCount > 6;
@endphp

<div class="contents" x-data="{ open: false, q: '' }">
    <button type="button" class="text-sm font-semibold text-violet" @click="open = true">
        {{ __('loop.change_branch') }}
    </button>

    <x-picker-layer
        :title="__('loop.choose_branch')"
        :search="$showSearch"
        :search-placeholder="__('loop.search')"
    >
        @foreach ($shops as $shop)
            <form
                method="POST"
                action="{{ route('till.branch') }}"
                data-loop-quiet
                x-show="!q.trim() || @js(strtolower($shop->name.' '.$shop->city)).includes(q.trim().toLowerCase())"
            >
                @csrf
                <input type="hidden" name="shop_id" value="{{ $shop->id }}">
                @if ($scanQuery)
                    <input type="hidden" name="scan" value="{{ $scanQuery }}">
                @endif
                <button
                    type="submit"
                    class="loop-picker-option"
                    @class(['is-selected' => $activeShop && (int) $activeShop->id === (int) $shop->id])
                >
                    <span>
                        <span class="block">{{ $shop->name }}</span>
                        @if ($shop->city)
                            <span class="mt-0.5 block text-sm font-normal text-ink-muted">{{ $shop->city }}</span>
                        @endif
                    </span>
                    @if ($activeShop && (int) $activeShop->id === (int) $shop->id)
                        <span class="ml-auto text-violet">✓</span>
                    @endif
                </button>
            </form>
        @endforeach
    </x-picker-layer>
</div>
