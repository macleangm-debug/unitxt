<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.admin_plans') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.admin_plans_blurb') }}</p>
    </x-slot>

    @include('admin.partials.nav')

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
                </p>
            </div>
        @endforeach
    </div>
</x-app-layout>
