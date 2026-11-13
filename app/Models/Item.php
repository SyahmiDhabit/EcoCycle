<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'business_id',
    'title',
    'name',
    'description',
    'sku',
    'category',
    'quantity',
    'unit',
    'price',
    'is_free',
    'listing_type',
    'item_type',
    'reusability',
    'condition',
    'minimum_threshold',
    'location_city',
    'location_state',
    'image_path',
    'image_1',
    'image_2',
    'image_3',
    'image_4',
    'image_5',
    'images',
    'status',
    'notes',
])]
class Item extends Model
{
    use HasFactory;

    protected $table = 'item';

    public function getNameAttribute(): ?string
    {
        return $this->attributes['name'] ?? $this->attributes['title'] ?? null;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $value;
        if (empty($this->attributes['title'])) {
            $this->attributes['title'] = $value;
        }
    }

    public function getTitleAttribute(): ?string
    {
        return $this->attributes['title'] ?? $this->attributes['name'] ?? null;
    }

    public function setTitleAttribute($value): void
    {
        $this->attributes['title'] = $value;
        if (empty($this->attributes['name'])) {
            $this->attributes['name'] = $value;
        }
    }

    public function getItemTypeAttribute(): ?string
    {
        return $this->attributes['item_type'] ?? $this->attributes['listing_type'] ?? 'buy';
    }

    public function setItemTypeAttribute($value): void
    {
        $this->attributes['listing_type'] = $value;
        $this->attributes['item_type'] = $value;
    }

    public function getListingTypeAttribute(): ?string
    {
        return $this->attributes['listing_type'] ?? $this->attributes['item_type'] ?? 'buy';
    }

    public function setListingTypeAttribute($value): void
    {
        $this->attributes['listing_type'] = $value;
        $this->attributes['item_type'] = $value;
    }

    public function getItemIdAttribute(): ?int
    {
        return $this->id;
    }

    public function getInventoryItemIdAttribute(): ?int
    {
        return $this->id;
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'minimum_threshold' => 'decimal:2',
            'price' => 'decimal:2',
            'is_free' => 'boolean',
            'images' => 'array',
        ];
    }

    /**
     * Return all image paths for this item.
     *
     * @return array<int, string>
     */
    public function getAllImages(): array
    {
        $explicit = array_filter([
            $this->image_1,
            $this->image_2,
            $this->image_3,
            $this->image_4,
            $this->image_5,
        ]);

        if (!empty($explicit)) {
            return array_values($explicit);
        }

        if (!empty($this->images) && is_array($this->images)) {
            return array_values(array_filter($this->images));
        }

        if (!empty($this->image_path)) {
            return [$this->image_path];
        }

        return [];
    }

    /**
     * Return the primary cover image URL/path.
     */
    public function getPrimaryImage(): ?string
    {
        $all = $this->getAllImages();
        return $all[0] ?? null;
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    public function exchanges(): HasMany
    {
        return $this->hasMany(MaterialExchange::class, 'item_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(MarketplaceReview::class, 'item_id');
    }

    public function wasteRecords(): HasMany
    {
        return $this->hasMany(WasteRecord::class, 'inventory_item_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'inventory_item_id');
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
