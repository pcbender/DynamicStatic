<?php
namespace Weaver\Tests;

use PHPUnit\Framework\TestCase;
use Weaver\WeaverConfig;

class WeaverConfigTest extends TestCase
{
    public function testConfigLoadsFromEnv(): void
    {
        $this->assertInstanceOf(WeaverConfig::class, $GLOBALS['weaverConfig']);
        /** @var WeaverConfig $config */
        $config = $GLOBALS['weaverConfig'];
        $this->assertSame('test-client', $config->weaverOauthClientId);
        $this->assertSame('google-client', $config->googleClientId);
    }
}
