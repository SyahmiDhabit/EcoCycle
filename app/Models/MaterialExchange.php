<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoice_number',
    'listing_id',
    'provider_business_id',
    'requester_business_id',
    'quantity',
    'unit',
    'total_price',
    'status',
    'payment_status',
    'payment_reference',
    'payment_requested_at',
    'paid_at',
    'notes',
    'completed_at',
])]
class MaterialExchange extends Model
{
    use HasFactory;

    protected $table = 'material_exchanges';

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'total_price' => 'decimal:2',
            'payment_requested_at' => 'datetime',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_business_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_business_id');
    }
}
