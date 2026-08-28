<x-app-layout>
    @php
        $usableSenders = $senderIds->filter(fn ($sender) => $sender->isUsable());
        $sendSteps = [
            1 => __('loop.sms_step_sender'),
            2 => __('loop.sms_step_audience'),
            3 => __('loop.sms_step_message'),
            4 => __('loop.sms_step_send'),
        ];
        $memberOptions = $members->mapWithKeys(fn ($member) => [$member->id => $member->name.' · '.$member->phone])->all();
        $senderOptions = $usableSenders->mapWithKeys(fn ($sender) => [$sender->id => $sender->code])->all();
    @endphp
    <x-slot name="header">
        <div class="flex items-start gap-3">
            <x-back-icon :href="route('customers.index')" />
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.customers') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.member_messages') }}</h1>
                <p class="mt-1 max-w-2xl text-ink-muted">{{ __('loop.member_messages_blurb') }}</p>
            </div>
        </div>
    </x-slot>

    @if (! empty($platformOff))
        <div class="rounded-[1.5rem] border border-ink/10 bg-ink px-5 py-6 text-white">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-lime">{{ __('loop.feature_paused') }}</p>
            <p class="mt-2 font-display text-xl font-semibold">{{ __('loop.feature_paused_sms_title') }}</p>
            <p class="mt-2 text-sm text-white/70">{{ __('loop.feature_paused_sms_body') }}</p>
        </div>
    @elseif (! $countrySupported)
        <div class="rounded-[1.5rem] border border-ink/10 bg-chalk/60 px-5 py-6">
            <p class="font-display text-xl font-semibold">{{ __('loop.sms_country_unsupported_title') }}</p>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.sms_country_unsupported') }}</p>
        </div>
    @elseif (! $planAllows)
        <div class="rounded-[1.5rem] border border-coral/25 bg-coral/10 px-5 py-6">
            <p class="font-display text-xl font-semibold">{{ __('loop.sms_plan_locked_title') }}</p>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.sms_plan_locked') }}</p>
            <a href="{{ route('billing.show') }}" class="loop-btn mt-4 inline-flex">{{ __('loop.upgrade_now') }}</a>
        </div>
    @else
        <div class="mb-6 loop-panel flex flex-wrap items-center justify-between gap-4 p-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.sms_credit_balance') }}</p>
                <p class="mt-1 font-display text-3xl font-semibold">{{ number_format($credits) }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.sms_credit_balance_help', ['price' => number_format($pricePerMessage), 'currency' => $currency, 'chars' => $charsPerMessage]) }}</p>
            </div>
            <form method="GET" action="{{ route('payments.show') }}" class="flex min-w-[12rem] flex-1 items-end gap-2 sm:max-w-sm">
                <input type="hidden" name="purpose" value="sms_credits">
                <div class="flex-1">
                    <label class="loop-label">{{ __('loop.sms_buy_how_many') }}</label>
                    <input type="number" name="credits" min="10" step="10" value="50" class="loop-input" required>
                </div>
                <button class="loop-btn !py-3">{{ __('loop.sms_buy_credits') }}</button>
            </form>
        </div>

        <section class="mb-8">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.sms_activated_ids') }}</h2>
            @if ($usableSenders->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($usableSenders as $sender)
                        <span class="rounded-full bg-violet px-4 py-2 text-sm font-semibold text-white">{{ $sender->code }}</span>
                    @endforeach
                </div>
            @else
                <p class="mt-2 text-sm text-ink-muted">{{ __('loop.need_active_sender') }}</p>
            @endif
            @if ($senderIds->reject(fn ($sender) => $sender->isUsable())->isNotEmpty())
                <div class="mt-3 space-y-2">
                    @foreach ($senderIds as $sender)
                        @if (! $sender->isUsable())
                            <div class="flex items-center justify-between rounded-2xl bg-chalk/70 px-4 py-2.5 text-sm">
                                <span class="font-semibold">{{ $sender->code }}</span>
                                <span class="text-ink-muted">{{ __('loop.sender_status_'.$sender->status) }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </section>

        <section class="mb-10 loop-panel space-y-4 p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.register_sender_id') }}</h2>
            <p class="text-sm text-ink-muted">{{ __('loop.sender_id_help', ['fee' => number_format($senderFee), 'currency' => $currency]) }}</p>

            @if ($platformSenderIds->isNotEmpty())
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.starter_sender_ids') }}</p>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($platformSenderIds as $starter)
                        <a href="{{ route('payments.show', ['purpose' => 'sender', 'starter' => $starter->id]) }}" class="flex items-center justify-between rounded-2xl border border-ink/10 bg-white px-4 py-3">
                            <span class="font-semibold">{{ $starter->code }}</span>
                            <span class="text-sm font-semibold text-violet">{{ __('loop.activate') }}</span>
                        </a>
                    @endforeach
                </div>
            @endif

            <form method="GET" action="{{ route('payments.show') }}" class="space-y-3">
                <input type="hidden" name="purpose" value="sender">
                <label class="loop-label">{{ __('loop.custom_sender_id') }}</label>
                <input name="code" maxlength="11" class="loop-input uppercase" placeholder="OFFER" required>
                <button class="loop-btn w-full">{{ __('loop.activate') }}</button>
            </form>
        </section>

        @if ($usableSenders->isNotEmpty())
            <section
                class="loop-panel p-6 sm:p-8"
                x-data="memberMessageWizard({
                    price: {{ (int) $pricePerMessage }},
                    chars: {{ (int) $charsPerMessage }},
                    credits: {{ (int) $credits }},
                    currency: @js($currency),
                    memberCount: {{ $members->count() }},
                    senderId: @js((string) $usableSenders->first()->id),
                })"
            >
                <h2 class="font-display text-xl font-semibold">{{ __('loop.send_sms') }}</h2>
                <x-form-stepper :steps="$sendSteps" />

                <form method="POST" action="{{ route('members.messages.store') }}" class="space-y-5">
                    @csrf

                    <div data-step="1" x-show="step === 1" class="space-y-4">
                        <x-sheet-select
                            name="sender_id_id"
                            :label="__('loop.sender_id')"
                            :options="$senderOptions"
                            :value="$usableSenders->first()->id"
                            :required="true"
                        />
                        <button type="button" class="loop-btn w-full" @click="next()">{{ __('loop.continue') }}</button>
                    </div>

                    <div data-step="2" x-show="step === 2" x-cloak class="space-y-4">
                        <label class="loop-label">{{ __('loop.audience') }}</label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ([
                                'all' => __('loop.all_members'),
                                'person' => __('loop.by_person'),
                                'gender' => __('loop.by_gender'),
                                'shops' => __('loop.by_branch'),
                                'groups' => __('loop.by_group'),
                                'points' => __('loop.by_points'),
                                'redeemed' => __('loop.by_redeemed'),
                            ] as $key => $label)
                                <label class="rounded-xl border border-ink/10 px-3 py-2 text-sm has-[:checked]:border-violet has-[:checked]:bg-violet-soft/50">
                                    <input type="radio" name="audience" value="{{ $key }}" class="sr-only" x-model="audience" @checked($key === 'all')> {{ $label }}
                                </label>
                            @endforeach
                        </div>

                        <div x-show="audience === 'person'" x-cloak>
                            <x-sheet-select
                                name="customer_id"
                                :label="__('loop.pick_member')"
                                :options="$memberOptions"
                                :value="old('customer_id')"
                            />
                        </div>
                        <div x-show="audience === 'gender'" x-cloak class="flex gap-3">
                            <label class="text-sm"><input type="checkbox" name="genders[]" value="male"> {{ __('loop.gender_male') }}</label>
                            <label class="text-sm"><input type="checkbox" name="genders[]" value="female"> {{ __('loop.gender_female') }}</label>
                        </div>
                        <div x-show="audience === 'shops'" x-cloak>
                            <div x-data="{ open: false }">
                                <button type="button" class="loop-input flex w-full items-center justify-between text-left" @click="open = true">
                                    <span>{{ __('loop.by_branch') }}</span>
                                    <span class="text-violet">▾</span>
                                </button>
                                <x-picker-layer :title="__('loop.by_branch')" :search="false">
                                    @foreach ($shops as $shop)
                                        <label class="flex items-center gap-3 px-1 py-2 text-sm">
                                            <input type="checkbox" name="shop_ids[]" value="{{ $shop->id }}"> {{ $shop->name }}
                                        </label>
                                    @endforeach
                                </x-picker-layer>
                            </div>
                        </div>
                        <div x-show="audience === 'groups'" x-cloak class="space-y-4">
                            <div class="flex gap-2">
                                <button type="button" class="flex-1 rounded-full px-4 py-2.5 text-sm font-semibold" :class="groupMode === 'pick' ? 'bg-violet text-white' : 'bg-chalk text-ink'" @click="groupMode = 'pick'">{{ __('loop.pick_existing_group') }}</button>
                                <button type="button" class="flex-1 rounded-full px-4 py-2.5 text-sm font-semibold" :class="groupMode === 'create' ? 'bg-violet text-white' : 'bg-chalk text-ink'" @click="groupMode = 'create'">{{ __('loop.create_group') }}</button>
                            </div>
                            <div x-show="groupMode === 'pick'">
                                @if ($groups->isNotEmpty())
                                    <div x-data="{ open: false }">
                                        <button type="button" class="loop-input flex w-full items-center justify-between text-left" @click="open = true">
                                            <span>{{ __('loop.by_group') }}</span>
                                            <span class="text-violet">▾</span>
                                        </button>
                                        <x-picker-layer :title="__('loop.by_group')" :search="count($groups) > 6">
                                            @foreach ($groups as $group)
                                                <label class="flex items-center gap-3 px-1 py-2 text-sm">
                                                    <input type="checkbox" name="group_ids[]" value="{{ $group->id }}"> {{ $group->name }} ({{ $group->members_count }})
                                                </label>
                                            @endforeach
                                        </x-picker-layer>
                                    </div>
                                @else
                                    <p class="text-sm text-ink-muted">{{ __('loop.no_groups_yet') }}</p>
                                @endif
                            </div>
                            <p x-show="groupMode === 'create'" x-cloak class="text-sm text-ink-muted">{{ __('loop.create_group_blurb') }}</p>
                        </div>
                        <div x-show="audience === 'points'" x-cloak>
                            <label class="loop-label">{{ __('loop.min_points') }}</label>
                            <input type="number" name="min_points" min="1" value="{{ old('min_points', 100) }}" class="loop-input">
                        </div>
                        <button type="button" class="loop-btn w-full" @click="next()">{{ __('loop.continue') }}</button>
                    </div>

                    <div data-step="3" x-show="step === 3" x-cloak class="space-y-4">
                        <label class="loop-label">{{ __('loop.message') }}</label>
                        <textarea name="body" rows="4" maxlength="480" class="loop-input" x-model="body" required></textarea>
                        <p class="text-xs text-ink-muted">
                            <span x-text="body.length"></span>/480
                            · <span x-text="segments()"></span> {{ __('loop.sms_segments_label') }}
                            · {{ __('loop.sms_chars_rule', ['chars' => $charsPerMessage]) }}
                        </p>
                        <button type="button" class="loop-btn w-full" @click="next()">{{ __('loop.continue') }}</button>
                    </div>

                    <div data-step="4" x-show="step === 4" x-cloak class="space-y-4">
                        <p class="text-sm text-ink-muted">{{ __('loop.sms_send_confirm_help') }}</p>
                        <p class="font-semibold" x-text="costLine()"></p>
                        <button class="loop-btn w-full">{{ __('loop.send_messages') }}</button>
                    </div>
                </form>
            </section>
        @endif

        <section class="mt-10 loop-panel space-y-4 p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.member_groups') }}</h2>
            <p class="text-sm text-ink-muted">{{ __('loop.create_group_blurb') }}</p>
            <form method="POST" action="{{ route('members.messages.groups') }}" class="space-y-3">
                @csrf
                <input name="name" class="loop-input" placeholder="{{ __('loop.group_name') }}" required>
                <p class="text-xs text-ink-muted">{{ __('loop.group_members_hint') }}</p>
                <div class="max-h-40 space-y-1 overflow-y-auto">
                    @foreach ($members as $member)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="member_ids[]" value="{{ $member->id }}"> {{ $member->name }}
                        </label>
                    @endforeach
                </div>
                <button class="loop-btn-ghost w-full">{{ __('loop.create_group') }}</button>
            </form>
        </section>
    @endif

    @if ($broadcasts->isNotEmpty())
        <section class="mt-10">
            <h2 class="mb-3 font-display text-xl font-semibold">{{ __('loop.recent_broadcasts') }}</h2>
            <div class="divide-y divide-ink/10">
                @foreach ($broadcasts as $row)
                    <div class="flex items-center justify-between py-3 text-sm">
                        <div>
                            <p class="font-semibold">{{ $row->sender_code }} · {{ $row->status }}</p>
                            <p class="text-ink-muted">{{ \Illuminate\Support\Str::limit($row->body, 80) }}</p>
                        </div>
                        <p>{{ $row->recipient_count }} · {{ $row->currency }} {{ number_format($row->cost) }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
