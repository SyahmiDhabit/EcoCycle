<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_id',
    'type',
    'title',
    'message',
    'link',
    'icon_type',
    'is_read',
    'read_at',
])]
class BusinessNotification extends Model
{
    use HasFactory;

    protected $table = 'business_notifications';

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    public static function send(
        int $businessId,
        string $title,
        string $message,
        string $type = 'general',
        ?string $link = null,
        string $iconType = 'green'
    ): self {
        return self::create([
            'business_id' => $businessId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'icon_type' => $iconType,
            'is_read' => false,
        ]);
    }
}
