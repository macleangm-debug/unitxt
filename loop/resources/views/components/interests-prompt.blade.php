@php
    $sectors = \App\Support\Sectors::all();
    $selected = old('interests', auth()->user()->interests ?? []);
@endphp

<section class="mb-8 overflow-hidden rounded-[1.75rem] border border-ink/10 bg-white p-6 shadow-[0_20px_60px_rgba(11,31,42,0.06)] sm:p-8">
    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ __('loop.interests') }}</p>
    <h2 class="mt-2 font-display text-2xl font-semibold">{{ __('loop.interests_prompt_title') }}</h2>
    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.interests_prompt_body') }}</p>

    <form method="POST" action="{{ route('preference.interests') }}" class="mt-5">
        @csrf
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
            @foreach ($sectors as $key => $label)
                @if ($key === 'other')
                    @continue
                @endif
                <label class="flex cursor-pointer items-center gap-2 rounded-2xl border border-ink/10 bg-chalk/40 px-3 py-3 text-sm has-[:checked]:border-mint-deep has-[:checked]:bg-mint-soft/50">
                    <input type="checkbox" name="interests[]" value="{{ $key }}" class="rounded border-ink/20 text-mint-deep focus:ring-mint-deep" @checked(in_array($key, $selected, true))>
                    <span class="font-medium">{{ $label }}</span>
                </label>
            @endforeach
        </div>
        <div class="mt-5 flex flex-wrap gap-3">
            <button class="loop-btn-mint flex-1 sm:flex-none">{{ __('loop.save_interests') }}</button>
            <button name="skip" value="1" class="loop-btn-ghost flex-1 sm:flex-none">{{ __('loop.skip_for_now') }}</button>
        </div>
    </form>
</section>
