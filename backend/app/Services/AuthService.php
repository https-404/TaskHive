<?php

namespace App\Services;

use App\DTOs\AuthResult;
use App\DTOs\LoginCredentials;
use App\DTOs\RefreshTokenResult;
use App\DTOs\RegisterUserData;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService
{
    /**
     * Access token TTL in minutes (short-lived).
     */
    protected function accessTokenTtlMinutes(): int
    {
        return (int) config('jwt.ttl', 15);
    }

    /**
     * Refresh token TTL in minutes (long-lived).
     */
    protected function refreshTokenTtlMinutes(): int
    {
        return (int) config('jwt.refresh_ttl', 20160); // 14 days
    }

    protected function apiGuard(): Guard
    {
        return Auth::guard('api');
    }

    /**
     * Login: validate credentials, issue short-lived access JWT + long-lived refresh token (stored in DB).
     *
     * @throws AuthenticationException
     */
    public function login(LoginCredentials $credentials): AuthResult
    {
        if (! $this->apiGuard()->attempt($credentials->toArray())) {
            throw new AuthenticationException(__('auth.failed'));
        }

        $user = $this->apiGuard()->user();
        if (! $user instanceof User) {
            throw new AuthenticationException(__('auth.failed'));
        }

        return $this->issueTokensForUser($user);
    }

    /**
     * Register: create user, issue access + refresh tokens.
     *
     * @throws ValidationException
     */
    public function register(RegisterUserData $data): AuthResult
    {
        try {
            $user = User::query()->create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
            ]);
        } catch (QueryException $e) {
            $this->handleRegistrationQueryException($e);
        }

        return $this->issueTokensForUser($user);
    }

    /**
     * Refresh: validate refresh token from body, rotate (delete old, create new), return new access + refresh.
     *
     * @throws AuthenticationException
     */
    public function refresh(string $refreshToken): RefreshTokenResult
    {
        $model = RefreshToken::findValidByToken($refreshToken);
        if (! $model) {
            throw new AuthenticationException(__('auth.token_invalid'));
        }

        $user = $model->user;
        $model->delete(); // rotation: one-time use

        $refreshTokenPlain = RefreshToken::createForUser($user, $this->refreshTokenTtlMinutes());
        $accessToken = JWTAuth::fromUser($user);
        $expiresIn = $this->accessTokenTtlMinutes() * 60;

        return new RefreshTokenResult(
            accessToken: $accessToken,
            refreshToken: $refreshTokenPlain,
            expiresIn: $expiresIn,
        );
    }

    /**
     * Logout: revoke refresh token (send refresh_token in body). Optionally invalidate current JWT if Bearer sent.
     */
    public function logout(?string $refreshToken = null): void
    {
        if ($refreshToken !== null && $refreshToken !== '') {
            $model = RefreshToken::findValidByToken($refreshToken);
            if ($model) {
                $model->delete();
            }
        }

        try {
            if ($this->apiGuard()->getToken()) {
                $this->apiGuard()->logout();
            }
        } catch (JWTException $e) {
            // Token missing or already invalid; ignore
        }
    }

    /**
     * Get the currently authenticated user (requires valid access token).
     *
     * @throws AuthenticationException
     */
    public function user(): User
    {
        $user = $this->apiGuard()->user();
        if (! $user instanceof User) {
            throw new AuthenticationException(__('auth.unauthenticated'));
        }
        return $user;
    }

    /**
     * Issue access token (JWT) + refresh token for user; store refresh token hash in DB.
     */
    protected function issueTokensForUser(User $user): AuthResult
    {
        $refreshTokenPlain = RefreshToken::createForUser($user, $this->refreshTokenTtlMinutes());
        $accessToken = JWTAuth::fromUser($user);
        $expiresIn = $this->accessTokenTtlMinutes() * 60;

        return new AuthResult(
            accessToken: $accessToken,
            refreshToken: $refreshTokenPlain,
            user: $user,
            expiresIn: $expiresIn,
        );
    }

    protected function handleRegistrationQueryException(QueryException $e): void
    {
        $message = $e->getMessage();
        if ($e->getCode() === '23000' || str_contains($message, 'Integrity constraint')) {
            if (str_contains($message, 'Duplicate') && str_contains($message, 'email')) {
                throw ValidationException::withMessages([
                    'email' => ['This email is already registered.'],
                ]);
            }
            throw ValidationException::withMessages([
                'email' => ['The given data is invalid.'],
            ]);
        }
        throw $e;
    }
}
