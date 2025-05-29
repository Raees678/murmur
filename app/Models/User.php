<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        'email',
        'password',
        'social_logins',
        'email_verified_at'
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
            'social_logins' => 'array',
        ];
    }

    public function hasSocialLogin(string $provider): bool
    {
        return isset($this->social_logins[$provider]);
    }

    public function getSocialLoginId(string $provider): ?string
    {
        return $this->social_logins[$provider]['id'] ?? null;
    }

    public function addSocialLogin(string $provider, string $id, array $data = []): void
    {
        $socialLogins = $this->social_logins ?? [];
        $socialLogins[$provider] = array_merge(['id' => $id], $data);
        $this->update(['social_logins' => $socialLogins]);
    }
}
