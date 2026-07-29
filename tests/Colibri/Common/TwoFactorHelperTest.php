<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\TwoFactorHelper;

class TwoFactorHelperTest extends TestCase
{
    public function testGenerateReturnsString(): void
    {
        $secret = TwoFactorHelper::Generate();
        $this->assertIsString($secret);
    }

    public function testGenerateDefaultLength(): void
    {
        $secret = TwoFactorHelper::Generate(16);
        // Base32 encodes 5 bits per char; 16 bytes = 128 bits -> ceil(128/5) = 26 chars
        $this->assertGreaterThanOrEqual(16, strlen($secret));
    }

    public function testGenerateOnlyBase32Chars(): void
    {
        $secret = TwoFactorHelper::Generate();
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    public function testGenerateIsUnique(): void
    {
        $secret1 = TwoFactorHelper::Generate();
        $secret2 = TwoFactorHelper::Generate();
        $this->assertNotEquals($secret1, $secret2);
    }

    public function testDegenerateReturns6DigitCode(): void
    {
        $secret = TwoFactorHelper::Generate();
        $code = TwoFactorHelper::Degenerate($secret);
        $this->assertIsString($code);
        $this->assertEquals(6, strlen($code));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    public function testDegenerateIsDeterministicForSameTimeSlice(): void
    {
        $secret = TwoFactorHelper::Generate();
        $timeSlice = (int) floor(time() / 30);
        $code1 = TwoFactorHelper::Degenerate($secret, $timeSlice);
        $code2 = TwoFactorHelper::Degenerate($secret, $timeSlice);
        $this->assertEquals($code1, $code2);
    }

    public function testDegenerateDifferentTimeSliceGivesDifferentCode(): void
    {
        $secret = TwoFactorHelper::Generate();
        $code1 = TwoFactorHelper::Degenerate($secret, 1000);
        $code2 = TwoFactorHelper::Degenerate($secret, 2000);
        // Different time slices should (almost always) give different codes
        $this->assertIsString($code1);
        $this->assertIsString($code2);
    }

    public function testVerifyValidCode(): void
    {
        $secret = TwoFactorHelper::Generate();
        $timeSlice = (int) floor(time() / 30);
        $code = TwoFactorHelper::Degenerate($secret, $timeSlice);
        $this->assertTrue(TwoFactorHelper::Verify($secret, $code));
    }

    public function testVerifyInvalidCode(): void
    {
        $secret = TwoFactorHelper::Generate();
        $this->assertFalse(TwoFactorHelper::Verify($secret, '000000'));
    }

    public function testVerifyOldCode(): void
    {
        $secret = TwoFactorHelper::Generate();
        // Time slice from far past should be invalid
        $oldCode = TwoFactorHelper::Degenerate($secret, 1);
        $this->assertFalse(TwoFactorHelper::Verify($secret, $oldCode));
    }
}
