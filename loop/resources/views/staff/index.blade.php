@php
    $addStaffErrors = filled(old('first_name')) || filled(old('last_name')) || filled(old('phone'));
    $staffDial = \App\Support\Countries::dial($business->country ?? 'TZ');
    $shopsFull = ($shops ?? collect())->count() > 1 && ($freeShopCount ?? 0) < 1;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex min-w-0 items-start gap-3">
                <x-back-icon :href="route('settings')" />
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.settings') }}</p>
                    <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.staff') }}</h1>
                    <p class="mt-1 max-w-2xl text-ink-muted">{{ __('loop.staff_page_blurb') }}</p>
                </div>
            </div>
            <button type="button" class="loop-btn-mint shrink-0" @click="$dispatch('open-add-staff')">{{ __('loop.add_staff') }}</button>
        </div>
    </x-slot>

    <div
        x-data="{ addOpen: {{ $addStaffErrors ? 'true' : 'false' }} }"
        @open-add-staff.window="addOpen = true"
    >
        <section>
            <h2 class="font-display text-xl font-semibold">{{ __('loop.your_team') }}</h2>
            <div class="mt-4 space-y-3">
                @forelse ($staff as $member)
                    <div class="rounded-[1.5rem] border border-ink/8 bg-white/90 px-5 py-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="font-display text-lg font-semibold">{{ $member->name }}</p>
                                <p class="mt-1 text-sm text-ink-muted">{{ $member->full_phone }}</p>
                                @if ($member->assignedShops->isNotEmpty())
                                    <p class="mt-1 text-xs text-ink-muted">{{ $member->assignedShops->pluck('name')->join(', ') }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $member->is_active ? 'bg-mint-soft text-ink' : 'bg-chalk text-ink-muted' }}">
                                    {{ $member->is_active ? __('loop.active') : __('loop.disabled') }}
                                </span>
                                <form method="POST" action="{{ route('staff.toggle', $member) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="text-sm font-semibold text-ink-muted hover:text-ink">
                                        {{ $member->is_active ? __('loop.disable') : __('loop.enable') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                        @if (($shops ?? collect())->count() > 1 && $member->is_active)
                            <form method="POST" action="{{ route('staff.shops', $member) }}" class="mt-4 space-y-2 border-t border-ink/8 pt-4">
                                @csrf
                                @method('PATCH')
                                <p class="loop-label">{{ __('loop.assign_branches') }}</p>
                                @foreach ($shops as $shop)
                                    @php
                                        $occupant = $staffByShop[$shop->id] ?? null;
                                        $takenByOther = $occupant && $occupant->id !== $member->id;
                                    @endphp
                                    <label class="flex items-center gap-2 text-sm {{ $takenByOther ? 'opacity-60' : '' }}">
                                        <input
                                            type="checkbox"
                                            name="shop_ids[]"
                                            value="{{ $shop->id }}"
                                            class="rounded border-ink/20 text-mint focus:ring-mint"
                                            @checked($member->assignedShops->contains('id', $shop->id))
                                            @disabled($takenByOther)
                                        >
                                        <span>
                                            {{ $shop->name }}
                                            @if ($takenByOther)
                                                <span class="text-xs text-ink-muted">· {{ $occupant->name }}</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                                <button class="loop-btn-ghost !min-h-0 !py-2">{{ __('loop.save_branches') }}</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="rounded-[1.5rem] border border-dashed border-ink/15 bg-chalk/40 px-5 py-8 text-center text-sm text-ink-muted">
                        <p>{{ __('loop.no_staff_yet') }}</p>
                        <button type="button" class="loop-btn-mint mt-5 inline-flex" @click="addOpen = true">{{ __('loop.add_staff') }}</button>
                    </div>
                @endforelse
            </div>
        </section>

        <x-loop-sheet model="addOpen" :title="__('loop.add_front_desk')" lock-swipe="true">
            <p class="text-sm text-ink-muted">{{ __('loop.add_front_desk_body') }}</p>

            <form method="POST" action="{{ route('staff.store') }}" id="add-staff" class="mt-5 space-y-4">
                @csrf
                @if ($shopsFull)
                    <p class="rounded-xl border border-ink/10 bg-chalk/60 px-4 py-3 text-sm text-ink-muted">{{ __('loop.all_shops_have_staff') }}</p>
                @endif
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="loop-label">{{ __('loop.first_name') }}</label>
                        <input name="first_name" value="{{ old('first_name') }}" class="loop-input" required>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.last_name') }}</label>
                        <input name="last_name" value="{{ old('last_name') }}" class="loop-input" required>
                    </div>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.phone') }}</label>
                    <x-phone-field
                        name="phone"
                        :dial="$staffDial"
                        hidden-dial-name="country_code"
                        :value="old('phone')"
                        :required="true"
                    />
                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.temp_password') }}</label>
                    <input type="password" name="password" class="loop-input" required>
                </div>
                @if (($shops ?? collect())->count() > 1)
                    <div>
                        <p class="loop-label">{{ __('loop.assign_branches') }}</p>
                        <p class="mb-2 text-xs text-ink-muted">{{ __('loop.assign_branches_help') }}</p>
                        <div class="space-y-2">
                            @foreach ($shops as $shop)
                                @php $occupant = $staffByShop[$shop->id] ?? null; @endphp
                                <label class="flex items-center gap-2 text-sm {{ $occupant ? 'opacity-60' : '' }}">
                                    <input
                                        type="checkbox"
                                        name="shop_ids[]"
                                        value="{{ $shop->id }}"
                                        class="rounded border-ink/20 text-mint focus:ring-mint"
                                        @checked(in_array($shop->id, old('shop_ids', [])))
                                        @disabled($occupant)
                                    >
                                    <span>
                                        {{ $shop->name }}
                                        @if ($occupant)
                                            <span class="text-xs text-ink-muted">· {{ $occupant->name }}</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
                <button class="loop-btn-mint w-full" @disabled($shopsFull)>{{ __('loop.add_front_desk') }}</button>
            </form>
        </x-loop-sheet>
    </div>
</x-app-layout>
