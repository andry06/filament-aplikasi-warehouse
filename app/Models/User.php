<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Transaction;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'avatar',
        'gender',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get all transactions for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Check if the avatar file exists in the public storage disk.
     *
     * @return bool True if the avatar exists, false otherwise.
     */
    public function getAvatarStorageExistsAttribute()
    {
        return $this->avatar ? Storage::disk('public')->exists($this->avatar) : false;
    }


    /**
     * Get the avatar URL.
     *
     * If the avatar file exists in the "public" storage disk, return its URL.
     * Otherwise, return the avatar string if it's not null, or the default avatar URL.
     *
     * @return string The avatar URL.
     */
    public function getAvatarUrlAttribute()
    {
        return $this->avatar ? ( Storage::disk('public')->exists($this->avatar) ? Storage::disk('public')->url($this->avatar) : $this->avatar ) : asset('assets/svg/icons/ic_no_profile.svg');
    }


}
