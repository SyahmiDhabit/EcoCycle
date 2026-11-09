<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'business_id',
    'name',
    'sku',
    'category',
    'quantity',
    'unit',
    'minimum_threshold',
    'status',
    'notes',
])]
class InventoryItem extends Model
{
    use HasFactory;

    protected $table = 'inventory_items';

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'minimum_threshold' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    public function wasteRecords(): HasMany
    {
        return $this->hasMany(WasteRecord::class, 'inventory_item_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'inventory_item_id');
    }

    public function marketplaceListings(): HasMany
    {
        return $this->hasMany(MarketplaceListing::class, 'inventory_item_id');
    }

    public function isLowStock(): bool
    {
        return $this->minimum_threshold !== null && $this->quantity <= $this->minimum_threshold && $this->quantity > 0;
    }

    public function isOutOfStock(): bool
    {
        return (float) $this->quantity <= 0;
    }
}
