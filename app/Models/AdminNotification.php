<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNotification extends Model
{
    use HasFactory;

    protected $table = 'admin_notification';

    protected $fillable = [
        'admin_id',
        'business_id',
        'type',
        'title',
        'message',
        'link',
        'is_read',
        'read_at',
    ];

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

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'admin_id');
    }

    public function markAsRead(): bool
    {
        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public static function send(
        string $type,
        string $title,
        string $message,
        ?string $link = '/admin/dashboard',
        ?int $businessId = null,
        ?int $adminId = null
    ): self {
        return self::create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link ?: '/admin/dashboard',
            'business_id' => $businessId,
            'admin_id' => $adminId,
            'is_read' => false,
        ]);
    }
}
