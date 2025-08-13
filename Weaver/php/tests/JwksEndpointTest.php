<?php
namespace Weaver\Tests;

use PHPUnit\Framework\TestCase;

class JwksEndpointTest extends TestCase
{
    public function testJwksEndpointReturnsKey(): void
    {
        ob_start();
        include __DIR__ . '/../.well-known/jwks.json.php';
        $json = ob_get_clean();
        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('keys', $data);
        $this->assertSame('test-key', $data['keys'][0]['kid']);
    }
}
