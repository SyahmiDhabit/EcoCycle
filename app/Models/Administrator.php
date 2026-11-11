<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class Administrator extends Authenticatable
{
    use Notifiable;

    protected $table = 'administrator';

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
