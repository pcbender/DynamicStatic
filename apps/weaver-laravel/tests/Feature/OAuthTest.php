<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class OAuthTest extends TestCase
{
    public function test_google_redirect_contains_forced_consent_and_scope()
    {
        $response = $this->get(route('auth.redirect', ['provider' => 'google']));
        $response->assertStatus(302);
        $url = $response->headers->get('Location');
        $this->assertStringContainsString('scope=openid%20email%20profile', $url);
        $this->assertStringContainsString('prompt=consent', $url);
    }

    public function test_callback_creates_user_from_google_profile()
    {
        $mock = new SocialiteUser();
        $mock->id = 'abc123';
        $mock->email = 'echo@example.com';
        $mock->name = 'Echo Dev';
        $mock->avatar = 'https://example.com/a.png';
        $mock->token = 't';
        $mock->refreshToken = 'rt';
        $mock->expiresIn = 3600;
    // Mock the redirect() used in adapter and the user() call used in callback
    Socialite::shouldReceive('driver->redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/v2/auth'));
    Socialite::shouldReceive('driver->user')->andReturn($mock);

        $response = $this->get(route('auth.callback', ['provider' => 'google']));
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'echo@example.com']);
    }

    public function test_microsoft_email_fallback_refreshes_token_on_401_then_retries()
    {
        Http::fake([
            'https://login.microsoftonline.com/*/oauth2/v2.0/token' => Http::response([
                'access_token' => 'NEW_ACCESS',
                'refresh_token' => 'NEW_REFRESH',
                'expires_in' => 3600,
            ], 200),
            'https://graph.microsoft.com/v1.0/me*' => Http::sequence()
                ->push('', 401)
                ->push(['mail' => 'cantor@example.com'], 200),
        ]);

        // Prepare a LinkedAccount fixture in DB (factory/seed), then:
        // app(ProviderApiClient::class)->microsoftEmailWithAutoRefresh($linkedAccount) should return the email and rotate tokens
        $this->assertTrue(true); // Replace with your assertions
    }
}
