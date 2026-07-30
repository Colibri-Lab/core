<?php

declare(strict_types=1);

namespace Colibri\Tests\Common;

use Colibri\Common\ValidationHelper;
use PHPUnit\Framework\TestCase;

final class ValidationHelperTest extends TestCase
{
    public function testBikReportsValidationErrors(): void
    {
        self::assertFalse(ValidationHelper::ValidateBik('', $message, $code));
        self::assertSame(1, $code);
        self::assertSame('БИК пуст', $message);

        self::assertFalse(ValidationHelper::ValidateBik('12345ABCD', $message, $code));
        self::assertSame(2, $code);

        self::assertTrue(ValidationHelper::ValidateBik('044525225', $message, $code));
    }

    public function testInnAndKppValidateFormatAndCheckDigits(): void
    {
        self::assertTrue(ValidationHelper::ValidateInn('7707083893'));
        self::assertFalse(ValidationHelper::ValidateInn('7707083894', $message, $code));
        self::assertSame(4, $code);

        self::assertTrue(ValidationHelper::ValidateKpp('773601001'));
        self::assertFalse(ValidationHelper::ValidateKpp('77360a001', $message, $code));
        self::assertSame(3, $code);
    }

    public function testSnilsValidatesCheckDigit(): void
    {
        self::assertTrue(ValidationHelper::ValidateSnils('11223344595'));
        self::assertFalse(ValidationHelper::ValidateSnils('11223344596', $message, $code));
        self::assertSame(4, $code);
    }
}
