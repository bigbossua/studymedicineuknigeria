<?php

namespace App\Models;

use App\Notifications\Auth\QueuedResetPassword;
use App\Notifications\Auth\QueuedVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['name', 'email', 'password', 'phone', 'whatsapp', 'country', 'nigeria_state'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'last_login_at' => 'datetime', 'two_factor_confirmed_at' => 'datetime', 'two_factor_secret' => 'encrypted', 'two_factor_recovery_codes' => 'encrypted:array'];
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new QueuedVerifyEmail);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new QueuedResetPassword($token));
    }

    public function applications()
    {
        return $this->hasMany(Application::class)->latest();
    }

    public function currentApplication(): ?Application
    {
        return $this->applications->first(fn (Application $a) => ! $a->isTerminal()) ?? $this->applications->first();
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['staff', 'admin'], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /** Staff and admin accounts cannot use the application without an authenticator app. */
    public function requiresTwoFactor(): bool
    {
        return $this->isStaff();
    }
}
