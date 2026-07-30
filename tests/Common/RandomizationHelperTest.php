<?php

declare(strict_types=1);

namespace Colibri\Tests\Common;

use Colibri\Common\RandomizationHelper;
use PHPUnit\Framework\TestCase;

final class RandomizationHelperTest extends TestCase
{
    public function testIntegerStaysWithinInclusiveRange(): void
    {
        for ($i = 0; $i < 20; $i++) {
            self::assertGreaterThanOrEqual(4, RandomizationHelper::Integer(4, 9));
            self::assertLessThanOrEqual(9, RandomizationHelper::Integer(4, 9));
        }
    }

    public function testGeneratedStringsUseExpectedAlphabetAndLength(): void
    {
        self::assertMatchesRegularExpression('/^[A-Za-z0-9]{32}$/', RandomizationHelper::Mixed(32));
        self::assertMatchesRegularExpression('/^[0-9]{32}$/', RandomizationHelper::Numeric(32));
        self::assertMatchesRegularExpression('/^[A-Za-z]{32}$/', RandomizationHelper::Character(32));
        self::assertSame('', RandomizationHelper::Mixed(0));
    }
}
