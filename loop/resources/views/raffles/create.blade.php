<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.create_raffle') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.create_raffle_blurb') }}</p>
    </x-slot>

    <form method="POST" action="{{ route('raffles.store') }}" class="mx-auto max-w-xl space-y-6 rounded-[2rem] border border-ink/10 bg-white/90 p-6 sm:p-8">
        @csrf
        <section class="space-y-4">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">1 · {{ __('loop.section_basics') }}</p>
            <div>
                <label class="loop-label">{{ __('loop.raffle_name') }}</label>
                <input name="name" class="loop-input" value="{{ old('name') }}" required placeholder="{{ __('loop.raffle_name_placeholder') }}">
            </div>
            <div>
                <label class="loop-label">{{ __('loop.description') }}</label>
                <textarea name="description" rows="2" class="loop-input">{{ old('description') }}</textarea>
            </div>
        </section>

        <section class="space-y-4 rounded-2xl bg-chalk/70 p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">2 · {{ __('loop.section_prize') }}</p>
            <p class="text-sm text-ink-muted">{{ __('loop.prize_like_offer') }}</p>
            <div>
                <label class="loop-label">{{ __('loop.prize_name') }}</label>
                <input name="prize_name" class="loop-input" value="{{ old('prize_name') }}" required placeholder="{{ __('loop.prize_name_placeholder') }}">
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="loop-label">{{ __('loop.type') }}</label>
                    <select name="prize_type" class="loop-input">
                        @foreach (['free_item','percent_off','fixed_off','custom'] as $t)
                            <option value="{{ $t }}" @selected(old('prize_type','custom')===$t)>{{ __('loop.'.$t) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.value_hint') }}</label>
                    <input type="number" step="0.01" name="prize_value" class="loop-input" value="{{ old('prize_value') }}">
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">3 · {{ __('loop.section_draw_rules') }}</p>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="loop-label">{{ __('loop.how_many_winners') }}</label>
                    <input type="number" min="1" max="{{ $maxWinners }}" name="winners_count" class="loop-input" value="{{ old('winners_count', 1) }}" required>
                    <p class="mt-1 text-xs text-ink-muted">{{ __('loop.raffle_winners_cap_help', ['max' => $maxWinners, 'pct' => $maxWinnersPercent, 'members' => $memberCount]) }}</p>
                    <x-input-error :messages="$errors->get('winners_count')" class="mt-1" />
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.frequency') }}</label>
                    <select name="frequency" class="loop-input">
                        @foreach (['once','weekly','monthly','yearly'] as $f)
                            <option value="{{ $f }}" @selected(old('frequency','once')===$f)>{{ __('loop.freq_'.$f) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.draw_date') }}</label>
                    <input type="date" name="draw_at" class="loop-input" value="{{ old('draw_at', now()->addWeek()->format('Y-m-d')) }}" required>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.claim_days') }}</label>
                    <input type="number" min="1" max="30" name="claim_days" class="loop-input" value="{{ old('claim_days', $defaultClaimDays) }}" required>
                    <p class="mt-1 text-xs text-ink-muted">{{ __('loop.claim_days_help') }}</p>
                </div>
            </div>
        </section>

        <button class="loop-btn-mint w-full">{{ __('loop.save_raffle') }}</button>
        <a href="{{ route('raffles.index') }}" class="block text-center text-sm text-ink-muted underline">{{ __('loop.back') }}</a>
    </form>
</x-app-layout>
