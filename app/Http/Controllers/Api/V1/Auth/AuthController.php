<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\DTOs\Auth\LoginData;
use App\DTOs\Auth\RegisterData;
use App\DTOs\Auth\ResetPasswordData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\V1\Auth\UserAuthResource;
use App\Services\AuthService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

#[Group('System - Auth')]
class AuthController extends Controller
{
    public function __construct(
        protected readonly AuthService $authService,
    ) {}

    /**
     * Register
     *
     * Register a new user. Returns user data and a JWT token.
     *
     * @unauthenticated
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register(
            RegisterData::fromArray($request->validated())
        );

        $user->load('roles');

        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('api');

        $token = $guard->login($user);

        if (! $token) {
            throw ValidationException::withMessages([
                'email' => ['Failed to generate token after registration.'],
            ]);
        }

        $tokenData = $this->authService->buildTokenData($token);

        return (new UserAuthResource($user, $tokenData, isNew: true))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Login
     *
     * Authenticate user credentials and return a JWT token.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): UserAuthResource
    {
        $this->ensureIsNotRateLimited($request, field: 'email', maxAttempts: 5, decaySeconds: 60);

        try {
            $tokenData = $this->authService->login(
                LoginData::fromArray($request->validated())
            );

            $this->clearThrottle($request, field: 'email');

            $user = Auth::guard('api')->user()->load('roles');

            return new UserAuthResource($user, $tokenData);
        } catch (ValidationException $e) {
            $this->hitThrottle($request, field: 'email', decaySeconds: 60);
            throw $e;
        }
    }

    /**
     * Refresh Token
     *
     * Refresh the current JWT token and return a new one.
     */
    public function refresh(): UserAuthResource
    {
        $tokenData = $this->authService->refresh();

        $user = Auth::guard('api')->user()->load('roles');

        return new UserAuthResource($user, $tokenData);
    }

    /**
     * Logout
     *
     * Revoke the current JWT token.
     */
    public function logout(): UserAuthResource
    {
        $user = Auth::guard('api')->user()->load('roles');
        $this->authService->revokeToken();

        return new UserAuthResource($user);
    }

    /**
     * Revoke Token (Alias for logout)
     *
     * Revoke the current JWT token.
     */
    public function revokeToken(): UserAuthResource
    {
        return $this->logout();
    }

    /**
     * Me
     *
     * Return the currently authenticated user.
     */
    public function me(): UserAuthResource
    {
        $user = Auth::guard('api')->user()->load('roles');

        return new UserAuthResource($user);
    }

    /**
     * Forgot Password
     *
     * Send a password reset link to the given email.
     *
     * @unauthenticated
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendPasswordResetLink(
            $request->validated('email')
        );

        return response()->json([
            'meta' => [
                'message' => 'Password reset link has been sent to your email.',
            ],
        ]);
    }

    /**
     * Reset Password
     *
     * Reset the user password using the token from email.
     *
     * @unauthenticated
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword(
            ResetPasswordData::fromArray($request->validated())
        );

        return response()->json([
            'meta' => [
                'message' => 'Password has been reset successfully.',
            ],
        ]);
    }

    /**
     * Verify Email
     *
     * This endpoint is used to verify a user's email address.
     *
     * **WARNING: DO NOT TEST VIA SCRAMBLE FORM!**
     * This endpoint requires Laravel's **Signed URL**. If you attempt to send
     * an API request directly from this documentation page, the system will
     * always return a `403 Invalid Signature` error.
     *
     * **How to test properly:**
     * 1. Call the `Register` or `Resend Verification Email` endpoint.
     * 2. Copy the **complete URL** from the received email (it must include the `?expires=...&signature=...` parameters).
     * 3. Paste the complete URL into a new browser tab or Postman.
     *
     * @unauthenticated
     */
    public function verifyEmail(string $id, string $hash): JsonResponse
    {
        if (! URL::hasValidSignature(request())) {
            throw ValidationException::withMessages([
                'email' => ['Invalid or expired verification link.'],
            ]);
        }

        $this->authService->verifyEmail($id, $hash);

        return response()->json([
            'meta' => [
                'message' => 'Email verified successfully.',
            ],
        ]);
    }

    /**
     * Resend Verification Email
     *
     * Resend the email verification link to the authenticated user.
     */
    public function resendVerificationEmail(): UserAuthResource
    {
        $user = Auth::guard('api')->user();

        $this->authService->resendVerificationEmail($user);

        return new UserAuthResource($user->load('roles'));
    }
}
