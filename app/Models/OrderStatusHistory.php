<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'material_exchange_id',
    'status',
    'note',
    'updated_by_business_id',
    'created_at',
])]
class OrderStatusHistory extends Model
{
    public $timestamps = false;

    protected $table = 'order_status_history';

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function exchange(): BelongsTo
    {
        return $this->belongsTo(MaterialExchange::class, 'material_exchange_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_business_id');
    }

    public function statusLabel(): string
    {
        return MaterialExchange::formatDeliveryStatus($this->status);
    }
}
