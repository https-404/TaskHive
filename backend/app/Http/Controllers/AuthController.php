<?php

namespace App\Http\Controllers;

use App\DTOs\LoginCredentials;
use App\DTOs\RegisterUserData;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $auth
    ) {}

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = RegisterUserData::fromArray($request->validated());
        $result = $this->auth->register($data);
        return response()->json($result->toArray(), 201);
    }

    /**
     * Login with email and password.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = LoginCredentials::fromArray($request->validated());
        $result = $this->auth->login($credentials);
        return response()->json($result->toArray());
    }

    /**
     * Refresh access token using refresh token (no Bearer required).
     */
    public function refresh(Request $request): JsonResponse
    {
        $request->validate(['refresh_token' => ['required', 'string']]);
        $result = $this->auth->refresh($request->input('refresh_token'));
        return response()->json($result->toArray());
    }

    /**
     * Get the currently authenticated user.
     */
    public function me(): JsonResponse
    {
        $user = $this->auth->user();
        return response()->json(['user' => $user]);
    }

    /**
     * Logout: revoke refresh token (send in body). Optionally invalidate current access token if Bearer sent.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->input('refresh_token'));
        return response()->json(null, 204);
    }
}
