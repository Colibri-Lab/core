<?php

declare(strict_types=1);

namespace Colibri\Tests\Collections;

use Colibri\Collections\Collection;
use Colibri\Collections\CollectionException;
use Colibri\Collections\ReadonlyCollection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CollectionTest extends TestCase
{
    public function testKeysAreCaseInsensitiveAndSupportArrayAccess(): void
    {
        $collection = new Collection(['Name' => 'Colibri']);
        $collection['VERSION'] = 1;

        self::assertSame(['name' => 'Colibri', 'version' => 1], $collection->ToArray());
        self::assertSame('Colibri', $collection->Name);
        self::assertTrue(isset($collection['name']));

        unset($collection['VERSION']);
        self::assertFalse($collection->Exists('version'));
    }

    public function testMutatingAndTransformingOperationsPreserveExpectedValues(): void
    {
        $collection = new Collection(['a' => 1, 'c' => 3]);
        $collection->Insert(1, 'b', 2);
        $collection->Append(['c' => null, 'd' => 4]);

        self::assertSame(['a' => 1, 'b' => 2, 'd' => 4], $collection->ToArray());
        self::assertSame(['b' => 2, 'd' => 4], $collection->Filter(static fn (string $key): bool => $key !== 'a')->ToArray());
        self::assertSame('a=1&b=2&d=4', $collection->ToString(['=', '&']));
        self::assertSame(['d' => 4], $collection->Extract(2, 2)->ToArray());
    }

    public function testInvalidOffsetAndReadonlyMutationsAreRejected(): void
    {
        $collection = new Collection();
        $this->expectException(InvalidArgumentException::class);
        $collection[0] = 'invalid';
    }

    public function testReadonlyCollectionRejectsChanges(): void
    {
        $collection = new ReadonlyCollection(['value' => 1]);
        $this->expectException(CollectionException::class);
        $collection->Add('other', 2);
    }
}
