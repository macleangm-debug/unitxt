<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.settings') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.staff') }}</h1>
                <p class="mt-1 max-w-2xl text-ink-muted">{{ __('loop.staff_page_blurb') }}</p>
            </div>
            <x-settings-back />
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-8" x-data="{ addOpen: {{ old('first_name') || old('phone') ? 'true' : 'false' }} }">
        <section class="rounded-[1.75rem] border border-ink/8 bg-white/90 p-5 sm:p-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.your_team') }}</h2>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.front_desk_list_blurb') }}</p>
                </div>
                <button type="button" class="loop-btn-mint !py-2.5" @click="addOpen = true">{{ __('loop.add_staff') }}</button>
            </div>
            <div class="mt-5 divide-y divide-ink/8 overflow-hidden rounded-[1.25rem] border border-ink/8">
                @forelse ($staff as $member)
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 bg-white px-4 py-4 sm:px-5"
                        x-data="{ confirmDisable: false }"
                    >
                        <div class="min-w-0">
                            <p class="font-display text-lg font-semibold">{{ $member->name }}</p>
                            <p class="mt-0.5 text-sm text-ink-muted">{{ $member->full_phone }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $member->is_active ? 'bg-lime/35 text-ink' : 'bg-chalk text-ink-muted' }}">
                                {{ $member->is_active ? __('loop.active') : __('loop.disabled') }}
                            </span>
                            @if ($member->is_active)
                                <button type="button" class="text-sm font-semibold text-coral hover:text-ink" @click="confirmDisable = true">
                                    {{ __('loop.disable') }}
                                </button>
                                <div
                                    x-show="confirmDisable"
                                    x-cloak
                                    class="fixed inset-0 z-[80] flex items-end justify-center bg-ink/50 p-4 sm:items-center"
                                    @keydown.escape.window="confirmDisable = false"
                                >
                                    <div class="absolute inset-0" @click="confirmDisable = false"></div>
                                    <div class="relative w-full max-w-md rounded-[1.5rem] bg-white p-6 shadow-2xl" @click.stop>
                                        <p class="font-display text-xl font-semibold">{{ __('loop.disable_staff_title') }}</p>
                                        <p class="mt-2 text-sm text-ink-muted">{{ __('loop.disable_staff_body', ['name' => $member->name]) }}</p>
                                        <div class="mt-5 flex gap-3">
                                            <button type="button" class="loop-btn-ghost flex-1" @click="confirmDisable = false">{{ __('loop.cancel') }}</button>
                                            <form method="POST" action="{{ route('staff.toggle', $member) }}" class="flex-1">
                                                @csrf
                                                @method('PATCH')
                                                <button class="loop-btn w-full !bg-coral">{{ __('loop.disable') }}</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <form method="POST" action="{{ route('staff.toggle', $member) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="text-sm font-semibold text-mint-deep hover:text-ink">{{ __('loop.enable') }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="bg-chalk/40 px-5 py-8 text-center text-sm text-ink-muted">
                        {{ __('loop.no_staff_yet') }}
                    </div>
                @endforelse
            </div>
        </section>

        <template x-teleport="body">
            <div
                x-show="addOpen"
                x-cloak
                class="fixed inset-0 z-[80] flex items-end justify-center bg-ink/50 p-0 sm:items-center sm:p-4"
                @keydown.escape.window="addOpen = false"
            >
                <div class="absolute inset-0" @click="addOpen = false"></div>
                <div class="relative max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-t-[1.75rem] bg-white p-5 shadow-2xl sm:rounded-[1.75rem] sm:p-7" @click.stop>
                    <div class="mx-auto mb-3 h-1.5 w-12 rounded-full bg-ink/15 sm:hidden"></div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.add_staff') }}</p>
                    <h2 class="mt-2 font-display text-2xl font-semibold">{{ __('loop.add_front_desk') }}</h2>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.add_front_desk_body') }}</p>

                    <form method="POST" action="{{ route('staff.store') }}" class="mt-6 space-y-4" autocomplete="off">
                        @csrf
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="loop-label">{{ __('loop.first_name') }}</label>
                                <input name="first_name" value="{{ old('first_name') }}" class="loop-input" required autocomplete="off">
                            </div>
                            <div>
                                <label class="loop-label">{{ __('loop.last_name') }}</label>
                                <input name="last_name" value="{{ old('last_name') }}" class="loop-input" required autocomplete="off">
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-[8rem_1fr]">
                            <div>
                                <label class="loop-label">{{ __('loop.country_prefix') }}</label>
                                <select name="country_code" class="loop-input">
                                    @foreach ($countries as $meta)
                                        <option value="{{ $meta['dial'] }}" @selected(old('country_code', \App\Support\Countries::dial($business->country)) === $meta['dial'])>{{ $meta['flag'] }} {{ $meta['dial'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="loop-label">{{ __('loop.phone') }}</label>
                                <input name="phone" value="{{ old('phone') }}" class="loop-input" required autocomplete="off" inputmode="tel">
                            </div>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.temp_password') }}</label>
                            <input type="password" name="password" class="loop-input" required autocomplete="new-password">
                        </div>
                        <div class="flex gap-3 pt-1">
                            <button type="button" class="loop-btn-ghost flex-1" @click="addOpen = false">{{ __('loop.cancel') }}</button>
                            <button class="loop-btn-mint flex-1">{{ __('loop.add_front_desk') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
