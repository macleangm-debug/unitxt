<?php

namespace App\Http\Controllers;

use App\Models\MemberGroup;
use App\Models\MessageBroadcast;
use App\Models\PlatformSenderId;
use App\Models\SenderId;
use App\Services\MessagingService;
use App\Services\Payments\PaymentService;
use App\Support\Confirm;
use App\Support\Countries;
use App\Support\FeatureFlags;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberMessageController extends Controller
{
    public function index(Request $request, MessagingService $messaging): View
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);
        $messaging->markExpiredSenderIds();

        return view('members.messages', [
            'business' => $business,
            'countrySupported' => $messaging->countrySupported($business->country),
            'planAllows' => $messaging->planAllows($business),
            'canMessage' => $messaging->businessCanMessage($business),
            'senderIds' => $business->senderIds()->latest()->get(),
            'platformSenderIds' => PlatformSenderId::query()->orderBy('sort_order')->get(),
            'groups' => $business->memberGroups()->withCount('members')->latest()->get(),
            'broadcasts' => $business->messageBroadcasts()->latest()->limit(20)->get(),
            'members' => $business->memberships()->with('customer')->get()->pluck('customer')->filter()->unique('id')->values(),
            'shops' => $business->shops()->where('is_active', true)->orderBy('name')->get(),
            'pricePerMessage' => $messaging->pricePerMessage(),
            'senderFee' => $messaging->senderYearlyFee(),
            'dial' => Countries::dial($business->country ?: 'TZ'),
            'country' => $business->country ?: 'TZ',
            'currency' => $business->currency ?: 'TZS',
            'platformOff' => ! FeatureFlags::enabled('sms_messaging'),
        ]);
    }

    public function storeSender(Request $request, MessagingService $messaging, PaymentService $payments): RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);
        abort_unless(FeatureFlags::enabled('sms_messaging'), 403);
        abort_unless($messaging->countrySupported($business->country), 403);

        $data = $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:11', 'regex:/^[A-Za-z0-9]+$/'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $code = strtoupper($data['code']);
        $sender = SenderId::query()->create([
            'business_id' => $business->id,
            'code' => $code,
            'kind' => 'custom',
            'status' => SenderId::STATUS_PENDING_PAYMENT,
            'yearly_fee' => $messaging->senderYearlyFee(),
        ]);

        $intent = $payments->startSenderIdPayment($business, $request->user(), $sender, $data['phone'], $business->country ?: 'TZ');

        return redirect()->route('payments.wait', $intent);
    }

    public function adoptStarter(Request $request, PlatformSenderId $platformSenderId, MessagingService $messaging, PaymentService $payments): RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);
        abort_unless(FeatureFlags::enabled('sms_messaging'), 403);
        abort_unless($messaging->countrySupported($business->country), 403);

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $sender = SenderId::query()->create([
            'business_id' => $business->id,
            'platform_sender_id_id' => $platformSenderId->id,
            'code' => $platformSenderId->code,
            'kind' => 'starter',
            'status' => SenderId::STATUS_PENDING_PAYMENT,
            'yearly_fee' => $platformSenderId->yearly_fee ?: $messaging->senderYearlyFee(),
        ]);

        $intent = $payments->startSenderIdPayment($business, $request->user(), $sender, $data['phone'], $business->country ?: 'TZ');

        return redirect()->route('payments.wait', $intent);
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer'],
        ]);

        $group = MemberGroup::query()->create([
            'business_id' => $business->id,
            'name' => $data['name'],
        ]);

        $ids = $business->memberships()
            ->whereIn('customer_id', $data['member_ids'] ?? [])
            ->pluck('customer_id')
            ->unique()
            ->all();
        $group->members()->sync($ids);

        return back()->with('confirm', Confirm::make(
            __('loop.group_created_title'),
            __('loop.group_created_body', ['name' => $group->name]),
            __('loop.done'),
            route('members.messages.index'),
            false,
        ));
    }

    public function storeBroadcast(Request $request, MessagingService $messaging, PaymentService $payments): RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);
        abort_unless($messaging->businessCanMessage($business), 403);

        $data = $request->validate([
            'sender_id_id' => ['required', 'integer'],
            'body' => ['required', 'string', 'max:480'],
            'audience' => ['required', 'in:all,shops,gender,groups,person,points,redeemed'],
            'shop_ids' => ['nullable', 'array'],
            'shop_ids.*' => ['integer'],
            'genders' => ['nullable', 'array'],
            'genders.*' => ['in:male,female'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer'],
            'customer_id' => ['required_if:audience,person', 'nullable', 'integer'],
            'min_points' => ['required_if:audience,points', 'nullable', 'integer', 'min:1'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $sender = $business->senderIds()->whereKey($data['sender_id_id'])->firstOrFail();
        abort_unless($sender->isUsable(), 422);

        $audience = [
            'audience' => $data['audience'],
            'shop_ids' => $data['shop_ids'] ?? [],
            'genders' => $data['genders'] ?? [],
            'group_ids' => $data['group_ids'] ?? [],
            'customer_id' => $data['customer_id'] ?? null,
            'min_points' => $data['min_points'] ?? null,
        ];
        $recipients = $messaging->recipients($business, $audience);
        if ($recipients->isEmpty()) {
            return back()->withInput()->withErrors(['audience' => __('loop.sms_no_recipients')]);
        }

        $cost = $recipients->count() * $messaging->pricePerMessage();
        $broadcast = MessageBroadcast::query()->create([
            'business_id' => $business->id,
            'user_id' => $request->user()->id,
            'sender_id_id' => $sender->id,
            'sender_code' => $sender->code,
            'body' => $data['body'],
            'audience' => $data['audience'],
            'audience_meta' => $audience,
            'recipient_count' => $recipients->count(),
            'cost' => $cost,
            'currency' => $business->currency ?: 'TZS',
            'status' => MessageBroadcast::STATUS_DRAFT,
            'purpose' => 'member_sms',
        ]);

        $intent = $payments->startSmsBroadcastPayment($business, $request->user(), $broadcast, $data['phone'], $business->country ?: 'TZ');

        return redirect()->route('payments.wait', $intent);
    }
}
