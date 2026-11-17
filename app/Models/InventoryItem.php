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
    'status',
    'reusability',
    'price',
    'is_free',
    'listing_type',
    'condition',
    'location_city',
    'location_state',
    'pickup_address',
    'image_path',
    'image_1',
    'image_2',
    'image_3',
    'images',
    'notes',
])]
class InventoryItem extends Model
{
    use HasFactory;

    protected $table = 'item';

    public function isPublishedToMarketplace(): bool
    {
        return ($this->reusability ?? '') === 'reusable'
            && in_array($this->attributes['status'] ?? '', ['active', 'sold'], true);
    }

    public function getStatusAttribute(): string
    {
        $raw = $this->attributes['status'] ?? 'in_stock';
        if (isset($this->attributes['quantity']) && (float) $this->attributes['quantity'] <= 0) {
            return 'out_of_stock';
        }
        if ($raw === 'active' || $raw === 'sold') {
            return (isset($this->attributes['quantity']) && (float) $this->attributes['quantity'] <= 0) ? 'out_of_stock' : 'in_stock';
        }
        return $raw;
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

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $value;
        if (empty($this->attributes['title'])) {
            $this->attributes['title'] = $value;
        }
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'price' => 'decimal:2',
            'images' => 'array',
        ];
    }

    /**
     * Return all image URLs/paths for this inventory item (up to 3 images: image_1 to image_3).
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
        return $this->hasMany(MarketplaceListing::class, 'id', 'id');
    }

    public function marketplaceListing(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MarketplaceListing::class, 'id', 'id');
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
