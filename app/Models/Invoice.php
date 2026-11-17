<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoice_number',
    'material_exchange_id',
    'payment_id',
    'seller_business_id',
    'buyer_business_id',
    'order_reference',
    'currency',
    'item_name',
    'item_description',
    'quantity',
    'unit',
    'unit_price',
    'subtotal',
    'tax_label',
    'tax_rate',
    'tax_amount',
    'total_amount',
    'payment_status',
    'payment_method',
    'payment_reference',
    'issued_at',
    'paid_at',
    'seller_snapshot',
    'buyer_snapshot',
])]
class Invoice extends Model
{
    use HasFactory;

    protected $table = 'invoice';

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
            'seller_snapshot' => 'array',
            'buyer_snapshot' => 'array',
        ];
    }

    public function exchange(): BelongsTo
    {
        return $this->belongsTo(MaterialExchange::class, 'material_exchange_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_business_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_business_id');
    }

    public function isAccessibleBy(int $businessId): bool
    {
        return $this->seller_business_id === $businessId || $this->buyer_business_id === $businessId;
    }

    public function formattedTotal(): string
    {
        return 'RM '.number_format((float) $this->total_amount, 2);
    }

    public function formattedSubtotal(): string
    {
        return 'RM '.number_format((float) $this->subtotal, 2);
    }

    public function formattedUnitPrice(): string
    {
        return 'RM '.number_format((float) $this->unit_price, 2);
    }

    public function formattedTaxAmount(): string
    {
        return 'RM '.number_format((float) $this->tax_amount, 2);
    }
}
