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
    'price',
    'is_free',
    'listing_type',
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
class MarketplaceListing extends Model
{
    use HasFactory;

    protected $table = 'listing';

    protected static function booted(): void
    {
        static::created(function (MarketplaceListing $listing): void {
            if (empty($listing->inventory_item_id)) {
                $listing->inventory_item_id = $listing->id;
                $listing->saveQuietly();
            }
        });
    }

    public function getStatusAttribute(): string
    {
        $raw = $this->attributes['status'] ?? 'active';
        if (isset($this->attributes['quantity']) && (float) $this->attributes['quantity'] <= 0) {
            return 'sold';
        }
        if ($raw === 'in_stock' || $raw === 'low_stock') {
            return 'active';
        }
        return $raw;
    }

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

    public function setTitleAttribute($value): void
    {
        $this->attributes['title'] = $value;
        if (empty($this->attributes['name'])) {
            $this->attributes['name'] = $value;
        }
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
     * Return all image paths for this listing (referencing image_1 to image_5 columns).
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
     * Return the primary image URL/path.
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

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'id', 'id');
    }

    public function exchanges(): HasMany
    {
        return $this->hasMany(MaterialExchange::class, 'listing_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(MarketplaceReview::class, 'listing_id');
    }
}
