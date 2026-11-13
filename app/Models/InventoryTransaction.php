<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_id',
    'inventory_item_id',
    'transaction_type',
    'quantity_change',
    'quantity_after',
    'notes',
])]
class InventoryTransaction extends Model
{
    use HasFactory;

    protected $table = 'inventory_transaction';

    protected function casts(): array
    {
        return [
            'quantity_change' => 'decimal:2',
            'quantity_after' => 'decimal:2',
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
