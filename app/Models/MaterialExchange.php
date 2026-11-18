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
    'item_name',
    'provider_business_id',
    'seller_business_name',
    'requester_business_id',
    'buyer_business_name',
    'listing_type',
    'quantity',
    'unit',
    'unit_price',
    'total_price',
    'collection_address',
    'fulfillment_method',
    'delivery_status',
    'status',
    'payment_status',
    'payment_reference',
    'payment_requested_at',
    'paid_at',
    'borrowed_returned_at',
    'notes',
    'completed_at',
])]
class MaterialExchange extends Model
{
    use HasFactory;

    public const DELIVERY_STATUS_LABELS = [
        'payment_pending' => 'Payment Pending',
        'confirmed' => 'Confirmed',
        'preparing' => 'Preparing for Collection / Delivery',
        'ready_for_collection' => 'Ready for Collection',
        'in_transit' => 'In Transit',
        'delivered' => 'Delivered / Collected',
        'completed' => 'Completed',
        'returned' => 'Returned',
        'cancelled' => 'Cancelled',
    ];

    protected $table = 'material_exchange';

    public static function formatDeliveryStatus(?string $status): string
    {
        $key = strtolower(trim((string) $status));

        return self::DELIVERY_STATUS_LABELS[$key] ?? 'Payment Pending';
    }

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
            'borrowed_returned_at' => 'datetime',
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

    public function listingTypeLabel(): string
    {
        return match ($this->resolvedListingType()) {
            'free' => 'Free',
            'lend' => 'Lend',
            default => 'Sell',
        };
    }

    public function requiresStripePayment(): bool
    {
        $type = $this->resolvedListingType();

        return $type === 'buy' && (float) $this->total_price > 0;
    }

    public function normalizedPaymentStatus(): string
    {
        $type = $this->resolvedListingType();
        $isNoCharge = in_array($type, ['free', 'lend'], true) || (float) $this->total_price <= 0;
        $raw = strtolower(trim((string) ($this->payment_status ?: ($isNoCharge ? 'free' : 'pending'))));

        if ($isNoCharge || $raw === 'free') {
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
            'free' => $this->resolvedListingType() === 'lend' ? 'Borrow (RM 0.00)' : 'Free',
            default => 'Pending',
        };
    }

    public function deliveryStatusLabel(): string
    {
        return self::formatDeliveryStatus($this->delivery_status ?: ($this->normalizedPaymentStatus() === 'pending' ? 'payment_pending' : 'confirmed'));
    }

    public function isReviewable(): bool
    {
        return in_array(strtolower((string) $this->delivery_status), ['delivered', 'completed', 'returned'], true);
    }

    /**
     * Suitable next delivery statuses for seller based on fulfillment method.
     *
     * @return array<int, string>
     */
    public function allowedNextStatusesForSeller(): array
    {
        $current = strtolower((string) ($this->delivery_status ?: 'payment_pending'));

        return match ($current) {
            'confirmed' => ['preparing', 'ready_for_collection', 'in_transit', 'cancelled'],
            'preparing' => ['ready_for_collection', 'in_transit', 'cancelled'],
            'ready_for_collection', 'in_transit' => ['delivered', 'completed'],
            'delivered' => ['completed'],
            default => [],
        };
    }

    public function recordStatusChange(string $status, ?int $updatedByBusinessId = null, ?string $note = null): OrderStatusHistory
    {
        return OrderStatusHistory::create([
            'material_exchange_id' => $this->id,
            'status' => $status,
            'note' => $note,
            'updated_by_business_id' => $updatedByBusinessId,
            'created_at' => now(),
        ]);
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

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'material_exchange_id')->orderBy('created_at')->orderBy('id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(MarketplaceReview::class, 'material_exchange_id');
    }
}
