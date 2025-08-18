<?php

namespace App\Http\Controllers\Auth;

use App\Services\Auth\OAuthService;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Services\Auth\TokenIssuer;
use Illuminate\Http\RedirectResponse;
use App\Models\User;
use App\Services\Auth\SocialUserNormalizer;
use Illuminate\Support\Facades\Crypt;
use App\Services\Auth\OAuthProviderAdapter;
use App\Models\LinkedAccount;
use Carbon\Carbon;
use App\Services\Auth\ProviderApiClient;

/**
 * OAuthController handles OAuth redirects and callbacks.
 */
class OAuthController
{
    protected OAuthService $oauthService;

    // Inject ProviderApiClient into the controller
    public function __construct(OAuthService $oauthService, private ProviderApiClient $providerApi)
    {
        $this->oauthService = $oauthService;
    }

    // Redirect to the OAuth provider
    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, ['google', 'microsoft'], true), 404);

        $scopes = $provider === 'google'
            ? ['openid','email','profile']
            : ['User.Read','offline_access'];

        $with = $provider === 'google'
            ? ['access_type' => 'offline','prompt' => 'consent','include_granted_scopes' => true,'response_type' => 'code']
            : [];

        return OAuthProviderAdapter::redirect($provider, $scopes, $with);
    }

    // Handle the callback from the OAuth provider
    public function callback(string $provider, Request $request): RedirectResponse
    {
        abort_unless(in_array($provider, ['google', 'microsoft'], true), 404);

    // For callback we use the raw Socialite driver (stateless) instead of the redirect adapter
    $socialiteUser = Socialite::driver($provider)->user();

        // Ensure $email is defined before persistence
        $norm   = SocialUserNormalizer::fromSocialite($provider, $socialiteUser);
        $email  = $norm['email'] ?? null;

        if (!$email && $provider === 'microsoft') {
            // Attempt to resolve via stored linked account if exists for token refresh / retrieval
            $maybeAccount = LinkedAccount::where('provider','microsoft')->where('provider_sub',$norm['sub'])->first();
            if ($maybeAccount) {
                try {
                    $resolved = $this->providerApi->microsoftEmailWithAutoRefresh($maybeAccount);
                    if ($resolved) {
                        $email = $resolved;
                    }
                } catch (\App\Exceptions\ReauthRequired $e) {
                    return OAuthProviderAdapter::redirect('microsoft', ['User.Read','offline_access'], ['prompt' => 'consent']);
                }
            }
        }

        if (!$email) {
            return redirect()->route('auth.collect-email', ['provider' => $provider, 'sub' => $norm['sub']]);
        }

        $user = User::firstOrCreate(
            ['email' => $email ?? $norm['email']],
            ['name' => $norm['name'], 'avatar' => $norm['avatar']]
        );

        // Replace save() with updateOrCreate for linked accounts
        $existing = LinkedAccount::where([
            'provider'      => $provider,
            'provider_sub'  => $norm['sub'],
        ])->first();

        $accessToken  = $norm['token'] ?? null;
        $refreshToken = $norm['refresh_token'] ?? null;

        if (!$refreshToken && $existing && $existing->refresh_token) {
            $refreshToken = $existing->refresh_token;
        }

        $expiresAt = !empty($norm['expires_in'])
            ? Carbon::now()->addSeconds((int) $norm['expires_in'])
            : null;

        $linked = LinkedAccount::updateOrCreate(
            [
                'provider'     => $provider,
                'provider_sub' => $norm['sub'],
            ],
            [
                'user_id'       => $user->id,
                'access_token'  => $accessToken  ? Crypt::encryptString($accessToken)   : null,
                'refresh_token' => $refreshToken ? Crypt::encryptString($refreshToken) : null,
                'expires_at'    => $expiresAt,
                'email'         => $email,
            ]
        );

        $token = app(TokenIssuer::class)->issueToken($user->id, $user->email);
        app(TokenIssuer::class)->setCookie($token);

        if (!$user->name) {
            return redirect('/onboarding');
        }

        return redirect('/dashboard');
    }

    // Manual re-auth trigger
    public function reauth(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, ['google','microsoft'], true), 404);
        $scopes = $provider === 'google'
            ? ['openid','email','profile']
            : ['User.Read','offline_access'];
        $with = $provider === 'google'
            ? ['access_type'=>'offline','prompt'=>'consent','include_granted_scopes'=>true,'response_type'=>'code']
            : ['prompt' => 'consent'];
        return OAuthProviderAdapter::redirect($provider, $scopes, $with);
    }

    // Display the email collection form
    public function collectEmailForm()
    {
        return view('auth.collect-email');
    }

    // Handle the email collection form submission
    public function collectEmailSubmit(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
        ]);

        $user = $request->user();
        if ($user) {
            $user->email = $request->input('email');
            $user->save();
        }

        return redirect('/dashboard')->with('success', 'Email updated successfully.');
    }
}
