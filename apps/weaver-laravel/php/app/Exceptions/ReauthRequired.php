<?php

namespace App\Exceptions;

use RuntimeException;

final class ReauthRequired extends RuntimeException
{
    public function __construct(public string $provider, string $reason = 'reauth required')
    {
        parent::__construct($reason);
    }
}
