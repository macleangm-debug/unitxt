<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.settings') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.staff') }}</h1>
            <p class="mt-1 max-w-2xl text-ink-muted">{{ __('loop.staff_page_blurb') }}</p>
        </div>
    </x-slot>

    <div class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">
        <section>
            <h2 class="font-display text-xl font-semibold">{{ __('loop.your_team') }}</h2>
            <div class="mt-4 space-y-3">
                @forelse ($staff as $member)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1.5rem] border border-ink/8 bg-white/90 px-5 py-4">
                        <div>
                            <p class="font-display text-lg font-semibold">{{ $member->name }}</p>
                            <p class="mt-1 text-sm text-ink-muted">{{ $member->full_phone }}</p>
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
                @empty
                    <div class="rounded-[1.5rem] border border-dashed border-ink/15 bg-chalk/40 px-5 py-8 text-center text-sm text-ink-muted">
                        {{ __('loop.no_staff_yet') }}
                    </div>
                @endforelse
            </div>
        </section>

        <section class="rounded-[2rem] border border-ink/8 bg-gradient-to-br from-white via-white to-mint/10 p-6 shadow-[0_20px_60px_rgba(11,31,42,0.06)] sm:p-7">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.add_staff') }}</p>
            <h2 class="mt-2 font-display text-2xl font-semibold">{{ __('loop.add_front_desk') }}</h2>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.add_front_desk_body') }}</p>

            <form method="POST" action="{{ route('staff.store') }}" class="mt-6 space-y-4">
                @csrf
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
                        <input name="phone" value="{{ old('phone') }}" class="loop-input" required>
                    </div>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.temp_password') }}</label>
                    <input type="password" name="password" class="loop-input" required>
                </div>
                <button class="loop-btn-mint w-full">{{ __('loop.add_front_desk') }}</button>
            </form>
        </section>
    </div>
</x-app-layout>
