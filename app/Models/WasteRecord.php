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
    'is_diverted',
    'status',
    'facility_name',
    'facility_location',
    'facility_payment_rm',
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
            'facility_payment_rm' => 'decimal:2',
            'tracking_history' => 'array',
            'is_diverted' => 'boolean',
            'recorded_at' => 'date',
        ];
    }

    public const STATUS_LABELS = [
        'in_storage' => 'In Storage',
        'scheduled_pickup' => 'Pickup Scheduled',
        'sent_to_facility' => 'Received by Facility',
        'recycled' => 'Recycled / Repurposed',
        'composted' => 'Composted',
    ];

    public function isFinalized(): bool
    {
        return in_array($this->status, ['recycled', 'composted'], true);
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    protected static function booted(): void
    {
        static::saving(function (WasteRecord $record): void {
            if ($record->exists && ! $record->isDirty(['status', 'facility_payment_rm'])) {
                return;
            }

            $history = $record->tracking_history ?? [];
            if ($record->exists && $history === []) {
                $history[] = [
                    'status' => $record->getOriginal('status'),
                    'payment' => $record->getOriginal('facility_payment_rm'),
                    'at' => null,
                ];
            }

            $history[] = [
                'status' => $record->status,
                'payment' => $record->facility_payment_rm,
                'at' => now()->toDateTimeString(),
            ];
            $record->tracking_history = $history;
        });
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
