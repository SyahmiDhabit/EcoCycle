<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password'])]
class Administrator extends Authenticatable
{
    use Notifiable;

    protected $table = 'administrator';

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function reviewedBusinesses(): HasMany
    {
        return $this->hasMany(User::class, 'reviewed_by');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AdminNotification::class, 'admin_id');
    }

    public function unreadNotifications(): HasMany
    {
        return $this->hasMany(AdminNotification::class, 'admin_id')->where('is_read', false);
    }
}
