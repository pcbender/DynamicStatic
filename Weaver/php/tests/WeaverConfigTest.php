<?php
namespace Weaver\Tests;

use PHPUnit\Framework\TestCase;
use Weaver\WeaverConfig;

class WeaverConfigTest extends TestCase
{
    public function testConfigLoadsFromEnv(): void
    {
        $config = WeaverConfig::getInstance();
        $this->assertInstanceOf(WeaverConfig::class, $config);
        $this->assertSame('test-client', $config->weaverOauthClientId);
        $this->assertSame('google-client', $config->googleClientId);
    }
}
