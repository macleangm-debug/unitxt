<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start gap-3">
            <x-back-icon :href="route('customers.index')" />
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.customers') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.member_messages') }}</h1>
                <p class="mt-1 max-w-2xl text-ink-muted">{{ __('loop.member_messages_blurb') }}</p>
            </div>
        </div>
    </x-slot>

    @if (! $countrySupported)
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
        <div class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr]" x-data="memberMessageWizard({
            price: {{ (int) $pricePerMessage }},
            currency: @js($currency),
            memberCount: {{ $members->count() }},
        })">
            <section class="loop-panel space-y-5 p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.send_sms') }}</p>
                <form method="POST" action="{{ route('members.messages.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="loop-label">{{ __('loop.sender_id') }}</label>
                        <select name="sender_id_id" class="loop-input" required>
                            <option value="">{{ __('loop.pick_option') }}</option>
                            @foreach ($senderIds as $sender)
                                @if ($sender->isUsable())
                                    <option value="{{ $sender->id }}">{{ $sender->code }}</option>
                                @endif
                            @endforeach
                        </select>
                        @if ($senderIds->contains(fn ($sender) => $sender->isUsable()) === false)
                            <p class="mt-1 text-xs text-coral">{{ __('loop.need_active_sender') }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.audience') }}</label>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <label class="rounded-xl border border-ink/10 px-3 py-2 text-sm has-[:checked]:border-mint has-[:checked]:bg-mint-soft/40">
                                <input type="radio" name="audience" value="all" class="sr-only" x-model="audience" checked> {{ __('loop.all_members') }}
                            </label>
                            <label class="rounded-xl border border-ink/10 px-3 py-2 text-sm has-[:checked]:border-mint has-[:checked]:bg-mint-soft/40">
                                <input type="radio" name="audience" value="gender" class="sr-only" x-model="audience"> {{ __('loop.by_gender') }}
                            </label>
                            <label class="rounded-xl border border-ink/10 px-3 py-2 text-sm has-[:checked]:border-mint has-[:checked]:bg-mint-soft/40">
                                <input type="radio" name="audience" value="shops" class="sr-only" x-model="audience"> {{ __('loop.by_branch') }}
                            </label>
                            <label class="rounded-xl border border-ink/10 px-3 py-2 text-sm has-[:checked]:border-mint has-[:checked]:bg-mint-soft/40">
                                <input type="radio" name="audience" value="groups" class="sr-only" x-model="audience"> {{ __('loop.by_group') }}
                            </label>
                        </div>
                    </div>
                    <div x-show="audience === 'gender'" x-cloak class="flex gap-3">
                        <label class="text-sm"><input type="checkbox" name="genders[]" value="male"> {{ __('loop.gender_male') }}</label>
                        <label class="text-sm"><input type="checkbox" name="genders[]" value="female"> {{ __('loop.gender_female') }}</label>
                    </div>
                    <div x-show="audience === 'shops'" x-cloak class="space-y-2">
                        @foreach ($shops as $shop)
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="shop_ids[]" value="{{ $shop->id }}"> {{ $shop->name }}</label>
                        @endforeach
                    </div>
                    <div x-show="audience === 'groups'" x-cloak class="space-y-2">
                        @forelse ($groups as $group)
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="group_ids[]" value="{{ $group->id }}"> {{ $group->name }} ({{ $group->members_count }})</label>
                        @empty
                            <p class="text-sm text-ink-muted">{{ __('loop.no_groups_yet') }}</p>
                        @endforelse
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.message') }}</label>
                        <textarea name="body" rows="4" maxlength="480" class="loop-input" x-model="body" required></textarea>
                        <p class="mt-1 text-xs text-ink-muted"><span x-text="body.length"></span>/480</p>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.pay_with_phone') }}</label>
                        <div class="flex gap-2">
                            <span class="inline-flex items-center rounded-2xl border border-ink/10 bg-chalk px-3 text-sm font-semibold">{{ $dial }}</span>
                            <input name="phone" class="loop-input !mt-0" placeholder="7XXXXXXXX" required>
                        </div>
                    </div>
                    <p class="text-sm font-semibold">{{ __('loop.sms_cost_hint', ['price' => number_format($pricePerMessage), 'currency' => $currency]) }}</p>
                    <button class="loop-btn-mint w-full" @disabled($senderIds->contains(fn ($sender) => $sender->isUsable()) === false)>{{ __('loop.pay_and_send') }}</button>
                </form>
            </section>

            <div class="space-y-6">
                <section class="loop-panel space-y-4 p-6">
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.register_sender_id') }}</h2>
                    <p class="text-sm text-ink-muted">{{ __('loop.sender_id_help', ['fee' => number_format($senderFee), 'currency' => $currency]) }}</p>
                    <form method="POST" action="{{ route('members.messages.sender') }}" class="space-y-3">
                        @csrf
                        <input name="code" maxlength="11" class="loop-input uppercase" placeholder="OFFER" required>
                        <div class="flex gap-2">
                            <span class="inline-flex items-center rounded-2xl border border-ink/10 bg-chalk px-3 text-sm font-semibold">{{ $dial }}</span>
                            <input name="phone" class="loop-input !mt-0" placeholder="7XXXXXXXX" required>
                        </div>
                        <button class="loop-btn w-full">{{ __('loop.pay_sender_id') }}</button>
                    </form>
                    <div class="space-y-2">
                        @foreach ($senderIds as $sender)
                            <div class="flex items-center justify-between rounded-xl bg-chalk/70 px-3 py-2 text-sm">
                                <span class="font-semibold">{{ $sender->code }}</span>
                                <span class="text-ink-muted">{{ $sender->status }}</span>
                            </div>
                        @endforeach
                    </div>
                    @if ($platformSenderIds->isNotEmpty())
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.starter_sender_ids') }}</p>
                        @foreach ($platformSenderIds as $starter)
                            <form method="POST" action="{{ route('members.messages.starter', $starter) }}" class="flex items-center justify-between gap-2">
                                @csrf
                                <span class="text-sm font-semibold">{{ $starter->code }}</span>
                                <input name="phone" class="loop-input !mt-0 !py-2" placeholder="7XXXXXXXX" required>
                                <button class="text-sm font-semibold text-violet">{{ __('loop.buy') }}</button>
                            </form>
                        @endforeach
                    @endif
                </section>

                <section class="loop-panel space-y-4 p-6">
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.member_groups') }}</h2>
                    <form method="POST" action="{{ route('members.messages.groups') }}" class="space-y-3">
                        @csrf
                        <input name="name" class="loop-input" placeholder="{{ __('loop.group_name') }}" required>
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
            </div>
        </div>

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
    @endif
</x-app-layout>
