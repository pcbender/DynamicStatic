<?php

namespace App\Services\Auth;

use Illuminate\Support\Arr;
use Laravel\Socialite\Facades\Socialite;

final class OAuthProviderAdapter
{
    /**
     * Generate a redirect response to the provider with appended scopes / params.
     */
    public static function redirect(string $provider, array $scopes = [], array $with = []): \Illuminate\Http\RedirectResponse
    {
        $drv = Socialite::driver($provider);
        $baseResponse = $drv->redirect();
        $url = $baseResponse->getTargetUrl();

        $params = $with;
        if ($scopes) {
            $params['scope'] = implode(' ', array_unique($scopes));
        }

        if ($params) {
            $sep = (parse_url($url, PHP_URL_QUERY) ? '&' : '?');
            // Build query string with %20 for spaces (tests expect %20 not +)
            $queryParts = [];
            foreach ($params as $k => $v) {
                $queryParts[] = rawurlencode($k) . '=' . rawurlencode($v);
            }
            $url .= $sep . implode('&', $queryParts);
        }

        return redirect()->away($url);
    }

    /**
     * Backwards compat shim if older code called make(); returns redirect.
     */
    public static function make(string $provider, array $scopes = [], array $with = []): \Illuminate\Http\RedirectResponse
    {
        return self::redirect($provider, $scopes, $with);
    }
}
