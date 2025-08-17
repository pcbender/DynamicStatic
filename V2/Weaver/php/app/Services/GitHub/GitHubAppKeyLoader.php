<?php

namespace App\Services\GitHub;

use RuntimeException;

class GitHubAppKeyLoader
{
    public function loadPrivateKey(): string
    {
        $path = trim((string) env('GITHUB_APP_PRIVATE_KEY_PATH', ''));
        if ($path !== '' && is_readable($path)) {
            $key = file_get_contents($path);
            if ($key === false || trim($key) === '') {
                throw new RuntimeException('GitHub App private key file is empty');
            }
            return $this->normalize($key);
        }

        $raw = env('GITHUB_APP_PRIVATE_KEY');
        if (is_string($raw) && $raw !== '') {
            if (str_contains($raw, '-----BEGIN')) {
                $raw = str_replace(['\\n', "\r\n", "\r"], "\n", $raw);
                return $this->normalize($raw);
            }
            if (is_readable($raw)) {
                $key = file_get_contents($raw);
                if ($key === false || trim($key) === '') {
                    throw new RuntimeException('GitHub App private key path provided is empty');
                }
                return $this->normalize($key);
            }
        }

        throw new RuntimeException('GitHub App private key not configured (set GITHUB_APP_PRIVATE_KEY_PATH or GITHUB_APP_PRIVATE_KEY).');
    }

    private function normalize(string $pem): string
    {
        return trim($pem) . "\n";
    }
}
