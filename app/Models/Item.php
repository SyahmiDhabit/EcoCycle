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
    'category',
    'quantity',
    'unit',
    'price',
    'is_free',
    'listing_type',
    'item_type',
    'reusability',
    'condition',
    'location_city',
    'location_state',
    'pickup_address',
    'image_path',
    'image_1',
    'image_2',
    'image_3',
    'images',
    'status',
    'notes',
])]
class Item extends Model
{
    use HasFactory;

    public const ALLOWED_CONDITIONS = [
        'New',
        'Damaged',
        'Used',
    ];

    /**
     * Canonical unit values mapped to dropdown display labels.
     *
     * @var array<string, string>
     */
    public const ALLOWED_UNITS = [
        'kg' => 'Kilogram (kg)',
        'g' => 'Gram (g)',
        'L' => 'Litre (L)',
        'mL' => 'Millilitre (mL)',
        'pcs' => 'Piece (pcs)',
        'Bag' => 'Bag',
        'Box' => 'Box',
        't' => 'Tonne (t)',
        'm' => 'Metre (m)',
        'm²' => 'Square Metre (m²)',
        'm³' => 'Cubic Metre (m³)',
    ];

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
            'price' => 'decimal:2',
            'is_free' => 'boolean',
            'images' => 'array',
        ];
    }

    /**
     * Return all image paths for this item (up to 3 images).
     *
     * @return array<int, string>
     */
    public function getAllImages(): array
    {
        $explicit = array_filter([
            $this->image_1,
            $this->image_2,
            $this->image_3,
        ]);

        if (! empty($explicit)) {
            return array_slice(array_values($explicit), 0, 3);
        }

        if (! empty($this->images) && is_array($this->images)) {
            return array_slice(array_values(array_filter($this->images)), 0, 3);
        }

        if (! empty($this->image_path)) {
            return [$this->image_path];
        }

        return [];
    }

    /**
     * Return exact 3-slot image array [slot1, slot2, slot3] preserving empty slots.
     *
     * @return array<int, string|null>
     */
    public function getImageSlots(): array
    {
        $slots = [
            $this->image_1 ?: null,
            $this->image_2 ?: null,
            $this->image_3 ?: null,
        ];

        if (! empty(array_filter($slots))) {
            return $slots;
        }

        $fallback = $this->getAllImages();

        return [
            $fallback[0] ?? null,
            $fallback[1] ?? null,
            $fallback[2] ?? null,
        ];
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
        return false;
    }

    public function isOutOfStock(): bool
    {
        return (float) $this->quantity <= 0;
    }
}
