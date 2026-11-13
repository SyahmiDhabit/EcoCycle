<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'item_id',
    'listing_id',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_business_id');
    }
}
