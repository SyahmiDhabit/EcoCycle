<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'material_exchange_id',
    'buyer_business_id',
    'seller_business_id',
    'provider',
    'settlement_account_type',
    'stripe_checkout_session_id',
    'stripe_payment_intent_id',
    'stripe_event_id',
    'idempotency_key',
    'currency',
    'amount',
    'amount_sen',
    'payment_method',
    'status',
    'failure_reason',
    'paid_at',
])]
class Payment extends Model
{
    use HasFactory;

    protected $table = 'payment';

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_sen' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function exchange(): BelongsTo
    {
        return $this->belongsTo(MaterialExchange::class, 'material_exchange_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_business_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_business_id');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'payment_id');
    }

    public function formattedAmount(): string
    {
        return 'RM '.number_format((float) $this->amount, 2);
    }

    public function referenceDisplay(): string
    {
        return $this->stripe_payment_intent_id
            ?: ($this->stripe_checkout_session_id ?: 'PAY-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT));
    }
}
