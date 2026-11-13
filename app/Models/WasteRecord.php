<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_id',
    'inventory_item_id',
    'material_name',
    'waste_type',
    'quantity',
    'unit',
    'disposal_method',
    'is_diverted',
    'status',
    'facility_name',
    'facility_location',
    'ai_recommendation',
    'cost_or_savings',
    'notes',
    'recorded_at',
])]
class WasteRecord extends Model
{
    use HasFactory;

    protected $table = 'waste_record';

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'cost_or_savings' => 'decimal:2',
            'is_diverted' => 'boolean',
            'recorded_at' => 'date',
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
}
