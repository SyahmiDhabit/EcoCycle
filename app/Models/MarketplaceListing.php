<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'business_id',
    'inventory_item_id',
    'title',
    'description',
    'category',
    'quantity',
    'unit',
    'price',
    'is_free',
    'listing_type',
    'reusability',
    'condition',
    'location_city',
    'location_state',
    'image_path',
    'status',
])]
class MarketplaceListing extends Model
{
    use HasFactory;

    protected $table = 'marketplace_listings';

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'price' => 'decimal:2',
            'is_free' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function exchanges(): HasMany
    {
        return $this->hasMany(MaterialExchange::class, 'listing_id');
    }
}
