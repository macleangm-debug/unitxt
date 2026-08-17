<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.admin_plans') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.admin_plans_blurb') }}</p>
    </x-slot>

    @include('admin.partials.nav')

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-mint-soft/50 px-4 py-3 text-sm text-ink">
        <div>
            <p class="font-semibold">{{ __('loop.settings_source_of_truth') }}</p>
            <p class="mt-1 text-ink-muted">{{ __('loop.plans_managed_in_hub') }}</p>
        </div>
        <a href="{{ route('admin.settings', ['tab' => 'packages']) }}" class="loop-btn-mint !py-2">{{ __('loop.edit_in_settings_hub') }} →</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($plans as $plan)
            <div class="loop-panel p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-display text-xl font-semibold">{{ $plan->name }}</p>
                        <p class="mt-1 text-sm text-ink-muted">{{ $plan->tagline }}</p>
                    </div>
                    <p class="font-semibold text-mint-deep">{{ $plan->priceLabel() }}</p>
                </div>
                <ul class="mt-4 space-y-1.5 text-sm text-ink-muted">
                    @foreach ($plan->features ?? [] as $feature)
                        <li>· {{ $feature }}</li>
                    @endforeach
                </ul>
                <p class="mt-4 text-xs text-ink-muted">
                    {{ __('loop.max_shops') }}: {{ $plan->max_shops ?? __('loop.unlimited') }} ·
                    {{ __('loop.max_members') }}: {{ $plan->max_members ?? __('loop.unlimited') }}
                    @if ($plan->max_monthly_visits)
                        · {{ __('loop.max_monthly_visits') }}: {{ $plan->max_monthly_visits }}
                    @endif
                    · {{ __('loop.max_product_pushes') }}: {{ $plan->max_product_pushes ?? __('loop.unlimited') }}
                    · {{ __('loop.max_offers') }}: {{ $plan->max_offers ?? __('loop.unlimited') }}
                </p>
            </div>
        @endforeach
    </div>
</x-app-layout>
