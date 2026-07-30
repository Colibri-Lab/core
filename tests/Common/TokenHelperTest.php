<?php

declare(strict_types=1);

namespace Colibri\Tests\Common;

use Colibri\Common\TokenHelper;
use PHPUnit\Framework\TestCase;

final class TokenHelperTest extends TestCase
{
    public function testGeneratedTokenValidatesWithItsKey(): void
    {
        $token = TokenHelper::Generate('test-key', 60);

        self::assertTrue(TokenHelper::Validate($token, 'test-key'));
        self::assertFalse(TokenHelper::Validate($token, 'other-key'));
    }

    public function testExpiredAndMalformedTokensAreRejected(): void
    {
        self::assertFalse(TokenHelper::Validate(TokenHelper::Generate('test-key', -1), 'test-key'));
        self::assertFalse(TokenHelper::Validate('not-a-token', 'test-key'));
        self::assertFalse(TokenHelper::Validate(base64_encode('missing-separator'), 'test-key'));
    }
}
