<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'business_name',
    'business_registration_number',
    'industry',
    'business_phone',
    'registered_address',
    'city',
    'postcode',
    'state',
    'profile_image',
    'ssm_certificate_path',
    'ssm_profile_path',
    'terms_accepted_at',
    'account_status',
    'review_note',
    'reviewed_by',
    'reviewed_at',
    'email',
    'password',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'business';

    public function getProfileImageUrlAttribute(): ?string
    {
        if (empty($this->profile_image)) {
            return null;
        }

        if (str_starts_with($this->profile_image, 'http://') || str_starts_with($this->profile_image, 'https://')) {
            return $this->profile_image;
        }

        return route('profile.image', ['filename' => basename($this->profile_image)]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'reviewed_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
        ];
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'reviewed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'business_id');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'business_id');
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'business_id');
    }

    public function wasteRecords(): HasMany
    {
        return $this->hasMany(WasteRecord::class, 'business_id');
    }

    public function marketplaceListings(): HasMany
    {
        return $this->hasMany(MarketplaceListing::class, 'business_id');
    }

    public function materialExchangesAsProvider(): HasMany
    {
        return $this->hasMany(MaterialExchange::class, 'provider_business_id');
    }

    public function materialExchangesAsRequester(): HasMany
    {
        return $this->hasMany(MaterialExchange::class, 'requester_business_id');
    }

    public function marketplaceReviews(): HasMany
    {
        return $this->hasMany(MarketplaceReview::class, 'seller_business_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(BusinessNotification::class, 'business_id')->latest();
    }

    public function unreadNotifications(): HasMany
    {
        return $this->hasMany(BusinessNotification::class, 'business_id')->where('is_read', false)->latest();
    }
}
