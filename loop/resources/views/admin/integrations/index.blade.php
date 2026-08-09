@php
    $tabs = [
        'overview' => __('loop.integrations_tab_overview'),
        'payments' => __('loop.integrations_tab_payments'),
        'console' => __('loop.integrations_tab_console'),
        'messaging' => __('loop.integrations_tab_messaging'),
        'email' => __('loop.integrations_tab_email'),
        'automation' => __('loop.integrations_tab_automation'),
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.admin') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.integrations_hub') }}</h1>
            <p class="mt-1 max-w-2xl text-ink-muted">{{ __('loop.integrations_hub_blurb') }}</p>
            <p class="mt-2 text-sm text-ink-muted">
                {{ __('loop.integrations_settings_note') }}
                <a href="{{ route('admin.settings') }}" class="font-semibold text-violet">{{ __('loop.admin_settings_hub') }} →</a>
            </p>
        </div>
    </x-slot>

    @include('admin.partials.nav')

    <div class="loop-admin-tabs" role="tablist">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.integrations.index', ['tab' => $key]) }}"
               class="loop-admin-tab {{ $tab === $key ? 'is-active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($tab === 'overview')
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div class="loop-glass p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.psp_health') }}</p>
                <p class="mt-2 font-display text-xl font-semibold">{{ $payinHealth['configured'] ? __('loop.configured') : __('loop.stub_mode') }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ $payinHealth['message'] }}</p>
            </div>
            <div class="loop-glass p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.primary_psp') }}</p>
                <p class="mt-2 font-display text-xl font-semibold uppercase">{{ $settings['payments']['primary'] }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.mode') }}: {{ $settings['payments']['providers']['payin']['mode'] }}</p>
            </div>
            <div class="loop-glass p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.messaging') }} / {{ __('loop.email') }}</p>
                <p class="mt-2 text-sm">{{ $settings['messaging']['enabled'] ? __('loop.on') : __('loop.off') }} · {{ $settings['email']['enabled'] ? __('loop.on') : __('loop.off') }}</p>
            </div>
        </div>
        <p class="mt-6 text-sm text-ink-muted">{{ __('loop.payin_docs_hint') }} <a class="font-semibold text-violet underline" href="https://docs.payin.co.tz/" target="_blank" rel="noopener">docs.payin.co.tz</a></p>
    @endif

    @if ($tab === 'payments')
        <div class="loop-glass p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.psp_settings') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.psp_settings_blurb') }}</p>
            <form method="POST" action="{{ route('admin.integrations.update') }}" class="mt-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="return_tab" value="payments">
                <input type="hidden" name="primary" value="payin">
                <x-admin.settings-lock>
                    <label class="flex items-center gap-2 text-sm font-semibold">
                        <input type="checkbox" name="payin[enabled]" value="1" @checked($settings['payments']['providers']['payin']['enabled'])>
                        {{ __('loop.enable_payin') }}
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="loop-label">{{ __('loop.payin_mode') }}</label>
                            <select name="payin[mode]" class="loop-input">
                                <option value="sandbox" @selected($settings['payments']['providers']['payin']['mode'] === 'sandbox')>Sandbox</option>
                                <option value="live" @selected($settings['payments']['providers']['payin']['mode'] === 'live')>Live</option>
                            </select>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.secondary_psp') }}</label>
                            <input name="secondary" value="{{ $settings['payments']['secondary'] }}" class="loop-input" placeholder="{{ __('loop.coming_soon') }}">
                        </div>
                        <div>
                            <label class="loop-label">X-API-Key</label>
                            <input name="payin[api_key]" value="{{ $settings['payments']['providers']['payin']['api_key'] }}" class="loop-input" autocomplete="off">
                        </div>
                        <div>
                            <label class="loop-label">X-API-Secret</label>
                            <input type="password" name="payin[api_secret]" value="{{ $settings['payments']['providers']['payin']['api_secret'] }}" class="loop-input" autocomplete="new-password">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="loop-label">{{ __('loop.webhook_secret') }}</label>
                            <input name="payin[webhook_secret]" value="{{ $settings['payments']['providers']['payin']['webhook_secret'] }}" class="loop-input">
                        </div>
                    </div>
                    <button class="loop-btn-mint">{{ __('loop.save') }}</button>
                </x-admin.settings-lock>
            </form>
        </div>

        <section class="mt-8 loop-glass p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.payment_flow_title') }}</h2>
            <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm text-ink-muted">
                <li>{{ __('loop.payment_flow_1') }}</li>
                <li>{{ __('loop.payment_flow_2') }}</li>
                <li>{{ __('loop.payment_flow_3') }}</li>
                <li>{{ __('loop.payment_flow_4') }}</li>
                <li>{{ __('loop.payment_flow_5') }}</li>
            </ol>
        </section>
    @endif

    @if ($tab === 'console')
        <section class="loop-glass p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.payment_console') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.payment_console_blurb') }}</p>
            <form method="POST" action="{{ route('admin.integrations.test-pay') }}" class="mt-5 grid gap-3 sm:grid-cols-4">
                @csrf
                <div>
                    <label class="loop-label">{{ __('loop.country') }}</label>
                    <select name="country" class="loop-input">
                        @foreach (\App\Support\Countries::enabledOptions() as $code => $meta)
                            <option value="{{ $code }}">{{ $meta['flag'] }} {{ $code }} · {{ $meta['currency'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.amount') }}</label>
                    <input type="number" name="amount" value="1000" min="100" class="loop-input" required>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.phone') }}</label>
                    <input name="phone" value="714123456" class="loop-input" required>
                </div>
                <div class="flex items-end">
                    <button class="loop-btn-mint w-full">{{ __('loop.run_test_payment') }}</button>
                </div>
            </form>
        </section>

        <section class="mt-8">
            <h2 class="mb-3 font-display text-xl font-semibold">{{ __('loop.recent_payments') }}</h2>
            <x-admin.empty-state :empty="$recentPayments->isEmpty()" :title="__('loop.recent_payments')">
                <div class="loop-table-wrap">
                    <table class="loop-table">
                        <thead>
                            <tr>
                                <th>{{ __('loop.when') }}</th>
                                <th>{{ __('loop.purpose') }}</th>
                                <th>{{ __('loop.amount') }}</th>
                                <th>{{ __('loop.status') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentPayments as $p)
                                <tr>
                                    <td>{{ $p->created_at?->diffForHumans() }}</td>
                                    <td>{{ $p->purpose }}</td>
                                    <td>{{ $p->currency }} {{ number_format($p->amount) }}</td>
                                    <td>{{ $p->status }}</td>
                                    <td class="text-right"><a href="{{ route('payments.wait', $p) }}" class="text-sm font-semibold text-violet">{{ __('loop.view') }} →</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-admin.empty-state>
        </section>
    @endif

    @if ($tab === 'messaging')
        <div class="loop-glass p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.messaging_integration') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.messaging_integration_blurb') }}</p>
            <form method="POST" action="{{ route('admin.integrations.update') }}" class="mt-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="return_tab" value="messaging">
                <x-admin.settings-lock>
                    <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="messaging[enabled]" value="1" @checked($settings['messaging']['enabled'])> {{ __('loop.enable_messaging') }}</label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="loop-label">{{ __('loop.sender_id') }}</label>
                            <input name="messaging[sender_id]" value="{{ $settings['messaging']['sender_id'] }}" class="loop-input">
                        </div>
                        <div>
                            <label class="loop-label">API key</label>
                            <input name="messaging[api_key]" value="{{ $settings['messaging']['api_key'] }}" class="loop-input">
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="messaging[business_can_message_customers]" value="1" @checked($settings['messaging']['business_can_message_customers'])> {{ __('loop.biz_message_customers') }}</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="messaging[platform_can_message_businesses]" value="1" @checked($settings['messaging']['platform_can_message_businesses'])> {{ __('loop.platform_message_businesses') }}</label>
                    <button class="loop-btn-mint">{{ __('loop.save') }}</button>
                </x-admin.settings-lock>
            </form>
        </div>
    @endif

    @if ($tab === 'email')
        <div class="loop-glass p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.email_integration') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.email_integration_blurb') }}</p>
            <form method="POST" action="{{ route('admin.integrations.update') }}" class="mt-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="return_tab" value="email">
                <x-admin.settings-lock>
                    <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="email[enabled]" value="1" @checked($settings['email']['enabled'])> {{ __('loop.enable_email') }}</label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="loop-label">{{ __('loop.from_name') }}</label>
                            <input name="email[from_name]" value="{{ $settings['email']['from_name'] }}" class="loop-input">
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.from_address') }}</label>
                            <input type="email" name="email[from_address]" value="{{ $settings['email']['from_address'] }}" class="loop-input">
                        </div>
                    </div>
                    <button class="loop-btn-mint">{{ __('loop.save') }}</button>
                </x-admin.settings-lock>
            </form>
        </div>
    @endif

    @if ($tab === 'automation')
        <div class="loop-glass p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.automated_comms') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.automated_comms_blurb') }}</p>
            <form method="POST" action="{{ route('admin.integrations.update') }}" class="mt-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="return_tab" value="automation">
                <x-admin.settings-lock>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="automation[holiday_messages]" value="1" @checked($settings['automation']['holiday_messages'])> {{ __('loop.holiday_messages') }}</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="automation[in_app_digest]" value="1" @checked($settings['automation']['in_app_digest'])> {{ __('loop.in_app_digest') }}</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="automation[trial_reminders]" value="1" @checked($settings['automation']['trial_reminders'])> {{ __('loop.trial_reminders') }}</label>
                    <button class="loop-btn-mint">{{ __('loop.save') }}</button>
                </x-admin.settings-lock>
            </form>
        </div>
    @endif
</x-app-layout>
