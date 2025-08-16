<?php

namespace App\Http\Controllers\Auth;

use App\Services\Auth\TokenIssuer;
use Illuminate\Http\Request;

/**
 * SelfAuthController handles login and registration for self-managed accounts.
 */
class SelfAuthController
{
    protected TokenIssuer $tokenIssuer;

    public function __construct(TokenIssuer $tokenIssuer)
    {
        $this->tokenIssuer = $tokenIssuer;
    }

    // Handle user login
    public function login(Request $request)
    {
        // ...implementation stub...
    }

    // Handle user registration
    public function register(Request $request)
    {
        // ...implementation stub...
    }
}
