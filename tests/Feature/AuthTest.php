<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'fixture-password';

    private const ORIGIN = 'http://localhost:5173';

    public function test_register_hashes_password_and_creates_an_authenticated_spa_session(): void
    {
        $this->assertFalse(Auth::check());

        $response = $this->withHeader('Origin', self::ORIGIN)->postJson('/api/register', [
            'name' => 'Fixture Registered User',
            'email' => 'registered@example.test',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'registered@example.test')
            ->assertJsonPath('data.profile.display_name', 'Fixture Registered User')
            ->assertJsonMissingPath('data.password');

        $user = User::where('email', 'registered@example.test')->sole();
        $this->assertNotSame(self::PASSWORD, $user->password);
        $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
        $this->assertSame('user', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertSame($user->id, Auth::id());
        $this->assertNotNull($user->profile);

        $this->getJson('/api/me')->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function test_user_create_uses_the_hashed_password_cast(): void
    {
        $user = User::create([
            'name' => 'Fixture Cast User',
            'email' => 'cast@example.test',
            'password' => self::PASSWORD,
            'role' => 'user',
            'status' => 'active',
        ]);

        $this->assertNotSame(self::PASSWORD, $user->password);
        $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
        $this->assertTrue(Hash::isHashed($user->fresh()->password));
    }

    public function test_spa_login_attempt_succeeds_and_logout_ends_the_session_and_token(): void
    {
        $user = User::factory()->create([
            'email' => 'spa@example.test',
            'password' => self::PASSWORD,
        ]);
        $this->assertTrue(Auth::attempt([
            'email' => 'spa@example.test',
            'password' => self::PASSWORD,
        ]));
        Auth::logout();

        $this->withHeader('Origin', self::ORIGIN)->getJson('/api/me')->assertUnauthorized();
        $this->withHeader('Origin', self::ORIGIN)->postJson('/api/login', [
            'email' => 'spa@example.test',
            'password' => self::PASSWORD,
        ])->assertOk()->assertJsonPath('data.id', $user->id);

        $this->assertAuthenticatedAs($user);
        $this->withHeader('Origin', self::ORIGIN)->getJson('/api/me')->assertOk();
        $sessionId = session()->getId();

        $this->withHeader('Origin', self::ORIGIN)->postJson('/api/logout')->assertOk();
        $this->assertNull(PersonalAccessToken::findToken($sessionId), 'Logout must revoke the current Sanctum token.');
        $this->assertNotSame($sessionId, session()->getId(), 'Logout must invalidate the session.');

        // Clear the test kernel's resolved user before making the next request.
        Auth::forgetGuards();
        $this->assertGuest('web');
        $this->withHeader('Origin', self::ORIGIN)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_suspended_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'suspended@example.test',
            'password' => self::PASSWORD,
            'status' => 'suspended',
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email')
            ->assertJsonPath('errors.email.0', 'Akun ini sedang ditangguhkan. Hubungi Admin.');

        $this->assertGuest();
    }
}
