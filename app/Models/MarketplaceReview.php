<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;

#[Fillable([
    'item_id',
    'listing_id',
    'material_exchange_id',
    'seller_business_id',
    'reviewer_business_id',
    'rating',
    'comment',
    'exchange_type',
])]
class MarketplaceReview extends Model
{
    use HasFactory;

    protected $table = 'marketplace_review';

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
            'rating' => 'integer',
        ];
    }

    /**
     * @param  Builder<MarketplaceReview>  $query
     */
    public function scopeFromItemBuyers(Builder $query): void
    {
        $query->whereExists(function (QueryBuilder $purchase): void {
            $purchase->selectRaw('1')
                ->from('material_exchange')
                ->whereColumn('material_exchange.item_id', 'marketplace_review.item_id')
                ->whereColumn('material_exchange.provider_business_id', 'marketplace_review.seller_business_id')
                ->whereColumn('material_exchange.requester_business_id', 'marketplace_review.reviewer_business_id')
                ->where(function (QueryBuilder $order): void {
                    $order->whereNull('marketplace_review.material_exchange_id')
                        ->orWhereColumn('material_exchange.id', 'marketplace_review.material_exchange_id');
                })
                ->where(function (QueryBuilder $order): void {
                    $order->where('material_exchange.payment_status', 'paid')
                        ->orWhereIn('material_exchange.delivery_status', ['delivered', 'completed', 'returned']);
                });
        });
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'item_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_business_id');
    }

    public function exchange(): BelongsTo
    {
        return $this->belongsTo(MaterialExchange::class, 'material_exchange_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_business_id');
    }
}
