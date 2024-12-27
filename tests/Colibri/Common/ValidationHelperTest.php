<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\ValidationHelper;

class ValidationHelperTest extends TestCase
{
    public function testValidateBik()
    {
        $this->assertTrue(ValidationHelper::ValidateBik('044525225'));
        $this->assertFalse(ValidationHelper::ValidateBik('invalid'));
    }

    public function testValidateInn()
    {
        $this->assertTrue(ValidationHelper::ValidateInn('7707083893'));
        $this->assertFalse(ValidationHelper::ValidateInn('invalid'));
    }

    public function testValidateKpp()
    {
        $this->assertTrue(ValidationHelper::ValidateKpp('773601001'));
        $this->assertFalse(ValidationHelper::ValidateKpp('invalid'));
    }

    public function testValidateKs()
    {
        $this->assertTrue(ValidationHelper::ValidateKs('30101810400000000225', '044525225'));
        $this->assertFalse(ValidationHelper::ValidateKs('invalid', '044525225'));
    }

    public function testValidateOgrn()
    {
        $this->assertTrue(ValidationHelper::ValidateOgrn('1027700132195'));
        $this->assertFalse(ValidationHelper::ValidateOgrn('invalid'));
    }

    public function testValidateOgrnip()
    {
        $this->assertTrue(ValidationHelper::ValidateOgrnip('304500116000157'));
        $this->assertFalse(ValidationHelper::ValidateOgrnip('invalid'));
    }

    public function testValidateRs()
    {
        $this->assertTrue(ValidationHelper::ValidateRs('40702810400000000001', '044525225'));
        $this->assertFalse(ValidationHelper::ValidateRs('invalid', '044525225'));
    }

    public function testValidateSnils()
    {
        $this->assertTrue(ValidationHelper::ValidateSnils('11223344595'));
        $this->assertFalse(ValidationHelper::ValidateSnils('invalid'));
    }
}
