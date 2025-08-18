<?php

namespace App\Console\Commands;

use App\Services\GitHub\GitHubAppClient;
use Illuminate\Console\Command;
use RuntimeException;

class WeaverGithubVerify extends Command
{
    protected $signature = 'weaver:github:verify {owner} {repo}';
    protected $description = 'Local-only smoke test for GitHub App installation connectivity';

    public function handle(GitHubAppClient $client)
    {
        if (!app()->environment('local')) {
            $this->error('This command is restricted to local environment.');
            return self::FAILURE;
        }
        $owner = $this->argument('owner');
        $repo = $this->argument('repo');
        try {
            $result = $client->resolveInstallationAndMint($owner, $repo);
            $payload = [
                'ok' => true,
                'installation_id' => (int) $result['installation_id'],
                'token_expires_at' => $result['token_expires_at'],
            ];
            $this->line(json_encode($payload, JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        } catch (RuntimeException $e) {
            $payload = [
                'ok' => false,
                'reason' => $e->getMessage(),
            ];
            $this->line(json_encode($payload, JSON_UNESCAPED_SLASHES));
            return self::FAILURE;
        } catch (\Throwable $e) {
            $payload = [
                'ok' => false,
                'reason' => 'Unexpected error',
            ];
            $this->line(json_encode($payload, JSON_UNESCAPED_SLASHES));
            return self::FAILURE;
        }
    }
}
