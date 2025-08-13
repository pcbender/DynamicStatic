<?php
namespace Weaver;

/**
 * Simple value object representing an authenticated user.
 */
class User
{
    public function __construct(
        public readonly string $sub,
        public readonly ?string $email = null,
        public readonly array $claims = []
    ) {
    }
}
