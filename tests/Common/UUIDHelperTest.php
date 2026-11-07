<?php

declare(strict_types=1);

namespace Colibri\Tests\Common;

use Colibri\Common\UUIDHelper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UUIDHelperTest extends TestCase
{
    public function testGeneratedRandomUuidIsValidVersionFour(): void
    {
        $uuid = UUIDHelper::v4();

        self::assertTrue(UUIDHelper::isValid($uuid));
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
    }

    public function testGeneratedUuidIsValidVersionSevenWithCurrentUnixTime(): void
    {
        $before = (int)floor(microtime(true) * 1000);
        $uuid = UUIDHelper::v7();
        $after = (int)floor(microtime(true) * 1000);

        self::assertTrue(UUIDHelper::isValid($uuid));
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
        $timestamp = (int)hexdec(substr(str_replace('-', '', $uuid), 0, 12));
        self::assertGreaterThanOrEqual($before, $timestamp);
        self::assertLessThanOrEqual($after, $timestamp);
    }

    public function testNameBasedUuidsAreDeterministicAndHaveExpectedVersion(): void
    {
        $namespace = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

        self::assertSame('3d813cbb-47fb-32ba-91df-831e1593ac29', UUIDHelper::v3($namespace, 'www.widgets.com'));
        self::assertSame('21f7f8de-8051-5b89-8680-0195ef798b6a', UUIDHelper::v5($namespace, 'www.widgets.com'));
    }

    public function testInvalidNamespaceIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UUIDHelper::v5('invalid', 'name');
    }
}
