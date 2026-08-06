<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ $business->name }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('Get live in about 5 minutes — logo, first shop, first campaign.') }}</p>
    </x-slot>

    <div class="mb-6 flex gap-2">
        @foreach ([1,2,3] as $n)
            <div class="h-1.5 flex-1 rounded-full {{ $step >= $n ? 'bg-gradient-to-r from-mint-deep to-coral' : 'bg-ink/10' }}"></div>
        @endforeach
    </div>

    @if ($step === 1)
        <form method="POST" action="{{ route('onboarding.logo') }}" enctype="multipart/form-data" class="loop-panel max-w-xl space-y-4 p-6">
            @csrf
            <h2 class="font-display text-xl font-semibold">1. {{ __('Add your business logo') }}</h2>
            <p class="text-sm text-ink-muted">{{ __('This is the first thing customers see on Loop.') }}</p>
            <div>
                <label class="loop-label">{{ __('Logo') }}</label>
                <input type="file" name="logo" accept="image/*" class="loop-input" required>
            </div>
            <div>
                <label class="loop-label">{{ __('How many branches / shops?') }}</label>
                <input type="number" min="1" max="50" name="branch_count" value="1" class="loop-input">
            </div>
            <button class="loop-btn">{{ __('loop.next') }}</button>
        </form>
    @elseif ($step === 2)
        <form method="POST" action="{{ route('onboarding.shop') }}" class="loop-panel max-w-xl space-y-4 p-6">
            @csrf
            <h2 class="font-display text-xl font-semibold">2. {{ __('Your first shop') }}</h2>
            <div>
                <label class="loop-label">{{ __('Shop name') }}</label>
                <input name="shop_name" value="{{ old('shop_name', $business->name) }}" class="loop-input" required>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.city') }}</label>
                <input name="city" list="cities" value="{{ old('city', $business->city) }}" class="loop-input" required>
                <datalist id="cities">
                    @foreach ($cities as $city)
                        <option value="{{ $city }}"></option>
                    @endforeach
                </datalist>
            </div>
            <div>
                <label class="loop-label">{{ __('Address') }}</label>
                <input name="address" class="loop-input">
            </div>
            <button class="loop-btn">{{ __('loop.next') }}</button>
        </form>
    @else
        <div class="max-w-2xl">
            <h2 class="font-display text-xl font-semibold">3. {{ __('Pick your first campaign') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('One tap. You can edit details later.') }}</p>
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                @foreach ($templates as $key => $template)
                    <form method="POST" action="{{ route('onboarding.campaign') }}">
                        @csrf
                        <input type="hidden" name="template" value="{{ $key }}">
                        <button class="loop-panel w-full p-5 text-left transition hover:bg-white">
                            <p class="font-display font-semibold">{{ $template['name'] }}</p>
                            <p class="mt-2 text-sm text-ink-muted">{{ $template['description'] }}</p>
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    @endif
</x-app-layout>
