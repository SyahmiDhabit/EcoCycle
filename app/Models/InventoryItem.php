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
    'name',
    'description',
    'sku',
    'category',
    'quantity',
    'unit',
    'minimum_threshold',
    'status',
    'reusability',
    'price',
    'is_free',
    'listing_type',
    'condition',
    'location_city',
    'location_state',
    'image_path',
    'image_1',
    'image_2',
    'image_3',
    'image_4',
    'image_5',
    'images',
    'notes',
])]
class InventoryItem extends Model
{
    use HasFactory;

    protected $table = 'item';

    protected static function booted(): void
    {
        static::created(function (InventoryItem $item): void {
            if (($item->reusability ?? 'reusable') === 'reusable' && empty($item->inventory_item_id)) {
                $item->inventory_item_id = $item->id;
                $item->saveQuietly();
            }
        });
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
            'minimum_threshold' => 'decimal:2',
            'price' => 'decimal:2',
            'images' => 'array',
        ];
    }

    /**
     * Return all image URLs/paths for this inventory item (referencing image_1 to image_5 columns).
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
        return $this->minimum_threshold !== null && $this->quantity <= $this->minimum_threshold && $this->quantity > 0;
    }

    public function isOutOfStock(): bool
    {
        return (float) $this->quantity <= 0;
    }
}
