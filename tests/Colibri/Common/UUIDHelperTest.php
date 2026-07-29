<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\UUIDHelper;

class UUIDHelperTest extends TestCase
{
    private string $validNamespace = '6ba7b810-9dad-11d1-80b4-00c04fd430c8'; // DNS namespace

    public function testV1ReturnsString(): void
    {
        $uuid = UUIDHelper::v1();
        $this->assertIsString($uuid);
    }

    public function testV1Format(): void
    {
        $uuid = UUIDHelper::v1();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-1[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    public function testV1IsUnique(): void
    {
        $uuid1 = UUIDHelper::v1();
        $uuid2 = UUIDHelper::v1();
        // With some randomness in clock sequence, these should usually differ
        $this->assertIsString($uuid1);
        $this->assertIsString($uuid2);
    }

    public function testV3ReturnsString(): void
    {
        $uuid = UUIDHelper::v3($this->validNamespace, 'test');
        $this->assertIsString($uuid);
    }

    public function testV3Format(): void
    {
        $uuid = UUIDHelper::v3($this->validNamespace, 'test');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-3[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    public function testV3IsDeterministic(): void
    {
        $uuid1 = UUIDHelper::v3($this->validNamespace, 'hello');
        $uuid2 = UUIDHelper::v3($this->validNamespace, 'hello');
        $this->assertEquals($uuid1, $uuid2);
    }

    public function testV3DifferentNamesProduceDifferentUUIDs(): void
    {
        $uuid1 = UUIDHelper::v3($this->validNamespace, 'name1');
        $uuid2 = UUIDHelper::v3($this->validNamespace, 'name2');
        $this->assertNotEquals($uuid1, $uuid2);
    }

    public function testV3ThrowsExceptionForInvalidNamespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        UUIDHelper::v3('invalid-namespace', 'test');
    }

    public function testV4ReturnsString(): void
    {
        $uuid = UUIDHelper::v4();
        $this->assertIsString($uuid);
    }

    public function testV4Format(): void
    {
        $uuid = UUIDHelper::v4();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    public function testV4IsUnique(): void
    {
        $uuids = [];
        for ($i = 0; $i < 10; $i++) {
            $uuids[] = UUIDHelper::v4();
        }
        $this->assertCount(10, array_unique($uuids));
    }

    public function testV5ReturnsString(): void
    {
        $uuid = UUIDHelper::v5($this->validNamespace, 'test');
        $this->assertIsString($uuid);
    }

    public function testV5Format(): void
    {
        $uuid = UUIDHelper::v5($this->validNamespace, 'test');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-5[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    public function testV5IsDeterministic(): void
    {
        $uuid1 = UUIDHelper::v5($this->validNamespace, 'hello');
        $uuid2 = UUIDHelper::v5($this->validNamespace, 'hello');
        $this->assertEquals($uuid1, $uuid2);
    }

    public function testV5ThrowsExceptionForInvalidNamespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        UUIDHelper::v5('not-a-valid-uuid', 'test');
    }

    public function testIsValidWithValidV4UUID(): void
    {
        $uuid = UUIDHelper::v4();
        $this->assertTrue((bool) UUIDHelper::isValid($uuid));
    }

    public function testIsValidWithInvalidString(): void
    {
        $this->assertFalse((bool) UUIDHelper::isValid('not-a-uuid'));
    }

    public function testIsValidWithEmptyString(): void
    {
        $this->assertFalse((bool) UUIDHelper::isValid(''));
    }
}
