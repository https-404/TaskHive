<?php

namespace App\DTOs;

use App\Models\User;

readonly class AuthResult
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public User $user,
        public int $expiresIn, // seconds
    ) {}

    /**
     * As array for JSON response.
     *
     * @return array{access_token: string, refresh_token: string, user: User, expires_in: int}
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'user' => $this->user,
            'expires_in' => $this->expiresIn,
        ];
    }
}
