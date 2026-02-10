<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RefreshToken extends Model
{
    protected $fillable = [
        'user_id',
        'token_hash',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Create a new refresh token for the user; returns the plain token (store hash in DB).
     */
    public static function createForUser(User $user, int $expiresInMinutes = 20160): string
    {
        $plain = Str::random(64);
        $hash = hash('sha256', $plain);

        self::query()->create([
            'user_id' => $user->id,
            'token_hash' => $hash,
            'expires_at' => now()->addMinutes($expiresInMinutes),
        ]);

        return $plain;
    }

    /**
     * Find a valid token by plain token string; returns the model or null.
     */
    public static function findValidByToken(string $plain): ?self
    {
        $hash = hash('sha256', $plain);

        $token = self::query()
            ->where('token_hash', $hash)
            ->first();

        if (! $token || $token->isExpired()) {
            return null;
        }

        return $token;
    }
}
