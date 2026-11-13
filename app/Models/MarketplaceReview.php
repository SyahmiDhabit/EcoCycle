<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
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

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_id');
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
