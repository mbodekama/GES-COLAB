<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'password',
        'last_login_at', 'last_login_ip',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'last_login_at'     => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    // ── Accessors ────────────────────────────────────────────
    public function getInitialsAttribute(): string
    {
        $parts = explode(' ', $this->name);
        return strtoupper(
            substr($parts[0], 0, 1) .
            (isset($parts[1]) ? substr($parts[1], 0, 1) : '')
        );
    }

    public function getPrimaryRoleLabelAttribute(): string
    {
        $role = $this->roles->first();
        return $role ? ucfirst($role->name) : 'Utilisateur';
    }

    // ── Helpers ──────────────────────────────────────────────
    public function unreadMessagesCount(): int
    {
        return $this->receivedMessages()->whereNull('read_at')->count();
    }
}
