<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_id',
    'user_id',
    'sender_id_id',
    'sender_code',
    'body',
    'audience',
    'audience_meta',
    'recipient_count',
    'cost',
    'currency',
    'status',
    'sent_at',
    'payment_intent_id',
    'sector',
    'template_key',
    'purpose',
])]
class MessageBroadcast extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected function casts(): array
    {
        return [
            'audience_meta' => 'array',
            'recipient_count' => 'integer',
            'cost' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function senderId(): BelongsTo
    {
        return $this->belongsTo(SenderId::class);
    }

    public function paymentIntent(): BelongsTo
    {
        return $this->belongsTo(PaymentIntent::class);
    }
}
