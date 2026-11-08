<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\TokenHelper;

class TokenHelperTest extends TestCase
{
    public function testGenerate(): void
    {
        $token = TokenHelper::Generate('my-secret-key');
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testGenerateIsBase64Encoded(): void
    {
        $token = TokenHelper::Generate('key');
        $decoded = base64_decode($token, true);
        $this->assertNotFalse($decoded);
    }

    public function testValidate(): void
    {
        $key = 'test-key-123';
        $token = TokenHelper::Generate($key, 300);
        $this->assertTrue(TokenHelper::Validate($token, $key));
    }

    public function testValidateWithWrongKey(): void
    {
        $token = TokenHelper::Generate('correct-key', 300);
        $this->assertFalse(TokenHelper::Validate($token, 'wrong-key'));
    }

    public function testValidateExpiredToken(): void
    {
        // Generate a token with TTL of -1 (already expired)
        $key = 'expire-test';
        $time = time();
        $expire = $time - 1; // already expired
        $hash = hash_hmac('sha256', $key . $expire, $key);
        $token = base64_encode($hash . '|' . $expire);
        $this->assertFalse(TokenHelper::Validate($token, $key));
    }

    public function testValidateInvalidBase64(): void
    {
        $this->assertFalse(TokenHelper::Validate('not-valid-base64!!!', 'key'));
    }

    public function testValidateMalformedToken(): void
    {
        // Valid base64 but missing pipe separator
        $token = base64_encode('invalidformat');
        $this->assertFalse(TokenHelper::Validate($token, 'key'));
    }

    public function testGenerateWithCustomTtl(): void
    {
        $key = 'my-key';
        $token = TokenHelper::Generate($key, 600);
        $decoded = base64_decode($token);
        $parts = explode('|', $decoded);
        $this->assertCount(2, $parts);
        $expire = (int) $parts[1];
        $this->assertGreaterThan(time(), $expire);
    }
}
