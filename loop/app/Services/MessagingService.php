<?php

namespace App\Services;

use App\Models\Business;
use App\Models\MemberGroup;
use App\Models\Membership;
use App\Models\MessageBroadcast;
use App\Models\SenderId;
use App\Models\User;
use App\Services\Messaging\SmsClient;
use App\Support\Countries;
use App\Support\FeatureFlags;
use App\Support\IntegrationSettings;
use Illuminate\Support\Collection;

class MessagingService
{
    public function __construct(private SmsClient $sms) {}

    /**
     * @return list<string>
     */
    public function enabledCountries(): array
    {
        $stored = IntegrationSettings::settings()['messaging']['enabled_countries'] ?? ['TZ'];

        return array_values(array_filter(array_map('strtoupper', (array) $stored)));
    }

    public function countrySupported(?string $country): bool
    {
        return in_array(strtoupper((string) $country), $this->enabledCountries(), true);
    }

    public function pricePerMessage(): int
    {
        return max(1, (int) (IntegrationSettings::settings()['messaging']['price_per_message'] ?? 30));
    }

    public function senderYearlyFee(): int
    {
        return max(0, (int) (IntegrationSettings::settings()['messaging']['sender_id_yearly_fee'] ?? 15000));
    }

    public function countryBlocked(Business $business): bool
    {
        return ! $this->countrySupported($business->country);
    }

    public function planAllows(Business $business): bool
    {
        return app(PlanLimitService::class)->smsEnabled($business);
    }

    public function businessCanMessage(Business $business): bool
    {
        $messaging = IntegrationSettings::settings()['messaging'] ?? [];

        return FeatureFlags::enabled('sms_messaging')
            && ! empty($messaging['business_can_message_customers'])
            && $this->countrySupported($business->country)
            && $this->planAllows($business);
    }

    public function health(): array
    {
        return $this->sms->health();
    }

    /**
     * @param  array{audience?: string, shop_ids?: array<int,int>, genders?: array<int,string>, group_ids?: array<int,int>}  $audience
     * @return Collection<int, User>
     */
    public function recipients(Business $business, array $audience): Collection
    {
        $memberIds = Membership::query()
            ->where('business_id', $business->id)
            ->pluck('customer_id')
            ->unique()
            ->values();

        $query = User::query()->whereIn('id', $memberIds);

        $type = $audience['audience'] ?? 'all';
        if ($type === 'gender') {
            $genders = array_values(array_intersect((array) ($audience['genders'] ?? []), ['male', 'female']));
            if ($genders !== []) {
                $query->whereIn('gender', $genders);
            }
        }

        if ($type === 'shops') {
            $shopIds = collect($audience['shop_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->all();
            if ($shopIds !== []) {
                $ids = Membership::query()
                    ->where('business_id', $business->id)
                    ->whereIn('shop_id', $shopIds)
                    ->pluck('customer_id');
                $query->whereIn('id', $ids);
            }
        }

        if ($type === 'groups') {
            $groupIds = collect($audience['group_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->all();
            if ($groupIds !== []) {
                $ids = MemberGroup::query()
                    ->where('business_id', $business->id)
                    ->whereIn('id', $groupIds)
                    ->with('members:id')
                    ->get()
                    ->flatMap(fn (MemberGroup $group) => $group->members->pluck('id'));
                $query->whereIn('id', $ids);
            }
        }

        return $query->get();
    }

    /**
     * @return list<string>
     */
    public function phonesFor(Collection $users, string $country): array
    {
        $dial = ltrim(Countries::dial($country), '+');

        return $users->map(function (User $user) use ($dial) {
            $digits = Countries::normalizePhone((string) $user->phone);

            return $dial.$digits;
        })->unique()->values()->all();
    }

    public function deliver(MessageBroadcast $broadcast, array $phones): MessageBroadcast
    {
        $result = $this->sms->send((string) $broadcast->sender_code, $phones, $broadcast->body);

        $broadcast->update([
            'status' => ($result['ok'] ?? false) ? MessageBroadcast::STATUS_SENT : MessageBroadcast::STATUS_FAILED,
            'sent_at' => ($result['ok'] ?? false) ? now() : null,
            'recipient_count' => $result['sent'] ?? count($phones),
        ]);

        return $broadcast->fresh();
    }

    public function testTo(string $sender, string $phone, string $body, string $country = 'TZ'): array
    {
        $dial = ltrim(Countries::dial($country), '+');
        $full = $dial.Countries::normalizePhone($phone);

        return $this->sms->send($sender, [$full], $body);
    }

    public function markExpiredSenderIds(): void
    {
        SenderId::query()
            ->where('status', SenderId::STATUS_ACTIVE)
            ->whereNotNull('paid_until')
            ->where('paid_until', '<', now())
            ->update(['status' => SenderId::STATUS_EXPIRED]);
    }
}
