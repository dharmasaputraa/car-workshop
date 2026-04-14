<?php

namespace Tests\Unit\Services;

use App\Actions\Auth\RegisterUser;
use App\Actions\Auth\ResetUserPassword;
use App\Actions\Auth\VerifyEmail;
use App\DTOs\Auth\LoginData;
use App\DTOs\Auth\RegisterData;
use App\DTOs\Auth\ResetPasswordData;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;
use Tymon\JWTAuth\JWTGuard;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    /** @var MockInterface|RegisterUser */
    protected $registerUserMock;

    /** @var MockInterface|VerifyEmail */
    protected $verifyEmailMock;

    /** @var MockInterface|ResetUserPassword */
    protected $resetUserPasswordMock;

    /** @var MockInterface|JWTGuard */
    protected $guardMock;

    /** @var MockInterface|PasswordBroker */
    protected $passwordBrokerMock;

    protected AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerUserMock = \Mockery::mock(RegisterUser::class);
        $this->verifyEmailMock = \Mockery::mock(VerifyEmail::class);
        $this->resetUserPasswordMock = \Mockery::mock(ResetUserPassword::class);
        $this->guardMock = \Mockery::mock(JWTGuard::class);
        $this->passwordBrokerMock = \Mockery::mock(PasswordBroker::class);

        $this->authService = new AuthService(
            $this->registerUserMock,
            $this->verifyEmailMock,
            $this->resetUserPasswordMock
        );
    }

    /*
    |--------------------------------------------------------------------------
    | READ OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function test_me_returns_authenticated_user(): void
    {
        $user = \Mockery::mock(User::class);

        Auth::shouldReceive('guard')
            ->once()
            ->with('api')
            ->andReturn($this->guardMock);

        $this->guardMock
            ->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $result = $this->authService->me();

        $this->assertSame($user, $result);
    }

    /*
    |--------------------------------------------------------------------------
    | REGISTER
    |--------------------------------------------------------------------------
    */

    public function test_register_delegates_to_register_user_action(): void
    {
        $dto = new RegisterData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123',
            passwordConfirmation: 'password123'
        );

        $user = \Mockery::mock(User::class);

        $this->registerUserMock
            ->shouldReceive('execute')
            ->once()
            ->with($dto)
            ->andReturn($user);

        $result = $this->authService->register($dto);

        $this->assertSame($user, $result);
    }

    /*
    |--------------------------------------------------------------------------
    | LOGIN
    |--------------------------------------------------------------------------
    */

    public function test_login_returns_token_data(): void
    {
        $dto = new LoginData(email: 'john@example.com', password: 'password123');
        $user = \Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')
            ->with('is_active')
            ->andReturn(true);

        Auth::shouldReceive('guard')
            ->twice() // Called twice: once in login(), once in buildTokenData()
            ->with('api')
            ->andReturn($this->guardMock);

        $this->guardMock
            ->shouldReceive('attempt')
            ->once()
            ->with(['email' => 'john@example.com', 'password' => 'password123'])
            ->andReturn('test-token');

        $this->guardMock
            ->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $this->guardMock
            ->shouldReceive('factory')
            ->once()
            ->andReturnSelf();

        $this->guardMock
            ->shouldReceive('getTTL')
            ->once()
            ->andReturn(60);

        $result = $this->authService->login($dto);

        $this->assertIsArray($result);
        $this->assertEquals('test-token', $result['access_token']);
        $this->assertEquals('bearer', $result['token_type']);
        $this->assertEquals(3600, $result['expires_in']); // 60 * 60
    }

    public function test_login_throws_for_invalid_credentials(): void
    {
        $dto = new LoginData(email: 'john@example.com', password: 'wrongpassword');

        Auth::shouldReceive('guard')
            ->once()
            ->with('api')
            ->andReturn($this->guardMock);

        $this->guardMock
            ->shouldReceive('attempt')
            ->once()
            ->with(['email' => 'john@example.com', 'password' => 'wrongpassword'])
            ->andReturn(false);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid credentials.');

        $this->authService->login($dto);
    }

    public function test_login_throws_for_inactive_user(): void
    {
        $dto = new LoginData(email: 'john@example.com', password: 'password123');
        $user = \Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')
            ->with('is_active')
            ->andReturn(false);

        Auth::shouldReceive('guard')
            ->once()
            ->with('api')
            ->andReturn($this->guardMock);

        $this->guardMock
            ->shouldReceive('attempt')
            ->once()
            ->andReturn('test-token');

        $this->guardMock
            ->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $this->guardMock
            ->shouldReceive('logout')
            ->once();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Your account has been deactivated.');

        $this->authService->login($dto);
    }

    /*
    |--------------------------------------------------------------------------
    | TOKEN OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function test_refresh_returns_new_token_data(): void
    {
        $user = \Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')
            ->with('is_active')
            ->andReturn(true);

        Auth::shouldReceive('guard')
            ->twice() // Called twice: once in refresh(), once in buildTokenData()
            ->with('api')
            ->andReturn($this->guardMock);

        $this->guardMock
            ->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $this->guardMock
            ->shouldReceive('refresh')
            ->once()
            ->andReturn('new-token');

        $this->guardMock
            ->shouldReceive('factory')
            ->once()
            ->andReturnSelf();

        $this->guardMock
            ->shouldReceive('getTTL')
            ->once()
            ->andReturn(60);

        $result = $this->authService->refresh();

        $this->assertIsArray($result);
        $this->assertEquals('new-token', $result['access_token']);
    }

    public function test_revoke_token_logouts(): void
    {
        Auth::shouldReceive('guard')
            ->once()
            ->with('api')
            ->andReturn($this->guardMock);

        $this->guardMock
            ->shouldReceive('logout')
            ->once();

        $this->authService->revokeToken();

        $this->assertTrue(true); // No exception thrown
    }

    public function test_build_token_data_returns_correct_structure(): void
    {
        Auth::shouldReceive('guard')
            ->once()
            ->with('api')
            ->andReturn($this->guardMock);

        $this->guardMock
            ->shouldReceive('factory')
            ->once()
            ->andReturnSelf();

        $this->guardMock
            ->shouldReceive('getTTL')
            ->once()
            ->andReturn(120);

        $result = $this->authService->buildTokenData('test-token');

        $this->assertEquals('test-token', $result['access_token']);
        $this->assertEquals('bearer', $result['token_type']);
        $this->assertEquals(7200, $result['expires_in']); // 120 * 60
    }

    /*
    |--------------------------------------------------------------------------
    | PASSWORD RESET
    |--------------------------------------------------------------------------
    */

    public function test_send_password_reset_link_succeeds(): void
    {
        Password::shouldReceive('broker')
            ->once()
            ->andReturn($this->passwordBrokerMock);

        $this->passwordBrokerMock
            ->shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => 'john@example.com'])
            ->andReturn(Password::RESET_LINK_SENT);

        $this->authService->sendPasswordResetLink('john@example.com');

        $this->assertTrue(true); // No exception thrown
    }

    public function test_send_password_reset_link_throws_on_failure(): void
    {
        Password::shouldReceive('broker')
            ->once()
            ->andReturn($this->passwordBrokerMock);

        $this->passwordBrokerMock
            ->shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => 'john@example.com'])
            ->andReturn(Password::INVALID_USER);

        $this->expectException(ValidationException::class);

        $this->authService->sendPasswordResetLink('john@example.com');
    }

    public function test_reset_password_succeeds(): void
    {
        $dto = new ResetPasswordData(
            token: 'reset-token',
            email: 'john@example.com',
            password: 'newpassword123',
            passwordConfirmation: 'newpassword123'
        );

        $user = \Mockery::mock(User::class);

        Password::shouldReceive('broker')
            ->once()
            ->andReturn($this->passwordBrokerMock);

        $this->passwordBrokerMock
            ->shouldReceive('reset')
            ->once()
            ->with($dto->toArray(), \Mockery::type('callable'))
            ->andReturn(Password::PASSWORD_RESET);

        $this->authService->resetPassword($dto);

        $this->assertTrue(true); // No exception thrown
    }

    public function test_reset_password_throws_on_failure(): void
    {
        $dto = new ResetPasswordData(
            token: 'invalid-token',
            email: 'john@example.com',
            password: 'newpassword123',
            passwordConfirmation: 'newpassword123'
        );

        Password::shouldReceive('broker')
            ->once()
            ->andReturn($this->passwordBrokerMock);

        $this->passwordBrokerMock
            ->shouldReceive('reset')
            ->once()
            ->with($dto->toArray(), \Mockery::type('callable'))
            ->andReturn(Password::INVALID_TOKEN);

        $this->expectException(ValidationException::class);

        $this->authService->resetPassword($dto);
    }

    /*
    |--------------------------------------------------------------------------
    | EMAIL VERIFICATION
    |--------------------------------------------------------------------------
    */

    public function test_verify_email_delegates_to_action(): void
    {
        $user = \Mockery::mock(User::class);

        $this->verifyEmailMock
            ->shouldReceive('execute')
            ->once()
            ->with('user-id', 'email-hash')
            ->andReturn($user);

        // verifyEmail returns void, just verify it doesn't throw
        $this->authService->verifyEmail('user-id', 'email-hash');

        $this->assertTrue(true);
    }

    public function test_verify_email_throws_when_action_fails(): void
    {
        $validator = Validator::make([], []);
        $exception = ValidationException::withMessages(['email' => ['Invalid verification link.']]);

        $this->verifyEmailMock
            ->shouldReceive('execute')
            ->once()
            ->with('user-id', 'invalid-hash')
            ->andThrow($exception);

        $this->expectException(ValidationException::class);

        $this->authService->verifyEmail('user-id', 'invalid-hash');
    }

    /*
    |--------------------------------------------------------------------------
    | RESEND VERIFICATION EMAIL
    |--------------------------------------------------------------------------
    */

    public function test_resend_verification_email_succeeds(): void
    {
        $user = \Mockery::mock(User::class);
        $user->shouldReceive('hasVerifiedEmail')
            ->once()
            ->andReturn(false);

        $user->shouldReceive('sendEmailVerificationNotification')
            ->once();

        $this->authService->resendVerificationEmail($user);

        $this->assertTrue(true); // No exception thrown
    }

    public function test_resend_verification_email_throws_if_already_verified(): void
    {
        $user = \Mockery::mock(User::class);
        $user->shouldReceive('hasVerifiedEmail')
            ->once()
            ->andReturn(true);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Email address is already verified.');

        $this->authService->resendVerificationEmail($user);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
