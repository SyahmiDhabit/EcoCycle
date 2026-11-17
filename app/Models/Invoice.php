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
    'buyer_business_name',
    'seller_business_name',
    'item_name',
    'quantity',
    'unit',
    'total_amount',
    'currency',
    'pickup_address',
    'payment_status',
    'issued_at',
])]
class Invoice extends Model
{
    use HasFactory;

    protected $table = 'invoice';

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'issued_at' => 'datetime',
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

    public function formattedQuantity(): string
    {
        $qty = rtrim(rtrim(number_format((float) $this->quantity, 2, '.', ''), '0'), '.');

        return trim($qty.' '.$this->unit);
    }

    public function statusLabel(): string
    {
        return match (strtolower((string) $this->payment_status)) {
            'free' => 'Free',
            'refunded' => 'Refunded',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            'pending' => 'Pending',
            default => 'Paid',
        };
    }
}
