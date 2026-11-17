<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'invoice_number',
    'order_reference',
    'item_id',
    'listing_id',
    'provider_business_id',
    'requester_business_id',
    'listing_type',
    'quantity',
    'unit',
    'unit_price',
    'total_price',
    'status',
    'payment_status',
    'payment_reference',
    'payment_requested_at',
    'paid_at',
    'notes',
    'completed_at',
])]
class MaterialExchange extends Model
{
    use HasFactory;

    protected $table = 'material_exchange';

    public function getItemIdAttribute(): ?int
    {
        return $this->attributes['item_id'] ?? null;
    }

    public function setItemIdAttribute($value): void
    {
        $this->attributes['item_id'] = $value;
    }

    public function getListingIdAttribute(): ?int
    {
        return $this->attributes['item_id'] ?? null;
    }

    public function setListingIdAttribute($value): void
    {
        $this->attributes['item_id'] = $value;
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'payment_requested_at' => 'datetime',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function resolvedOrderReference(): string
    {
        return $this->order_reference
            ?: ($this->invoice_number ?: 'ECO-ORD-'.date('Y').'-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT));
    }

    public function resolvedListingType(): string
    {
        return $this->listing_type ?: ($this->listing?->listing_type ?: 'buy');
    }

    public function requiresStripePayment(): bool
    {
        $type = $this->resolvedListingType();

        return $type === 'buy' && (float) $this->total_price > 0;
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'item_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_business_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_business_id');
    }

    public function normalizedPaymentStatus(): string
    {
        $isFree = $this->resolvedListingType() === 'free' || (float) $this->total_price <= 0;
        $raw = strtolower(trim((string) ($this->payment_status ?: ($isFree ? 'free' : 'pending'))));

        if ($isFree || $raw === 'free') {
            return 'free';
        }

        return match ($raw) {
            'paid' => 'paid',
            'failed' => 'failed',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            default => 'pending',
        };
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->normalizedPaymentStatus()) {
            'paid' => 'Paid',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            'free' => 'Free',
            default => 'Pending',
        };
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'material_exchange_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'material_exchange_id')->latest();
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class, 'material_exchange_id')->latestOfMany();
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'material_exchange_id');
    }
}
