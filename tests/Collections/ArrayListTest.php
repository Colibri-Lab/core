<?php

declare(strict_types=1);

namespace Colibri\Tests\Collections;

use Colibri\Collections\ArrayList;
use PHPUnit\Framework\TestCase;

final class ArrayListTest extends TestCase
{
    public function testListMutationAndArrayAccess(): void
    {
        $list = new ArrayList([1, 3]);
        $list->InsertAt(2, 1);
        $list[] = 4;
        $list[0] = 0;
        unset($list[2]);

        self::assertSame([0, 2, 4], $list->ToArray());
        self::assertSame(2, $list->IndexOf(4));
        self::assertTrue($list->Delete(2));
        self::assertFalse($list->Delete(99));
    }

    public function testListFunctionalOperationsReturnExpectedLists(): void
    {
        $list = new ArrayList([3, 1, 2]);

        self::assertSame([2], $list->Filter(static fn (int $value): bool => $value === 2)->ToArray());
        self::assertSame([6, 2, 4], $list->Map(static fn (int $value): int => $value * 2)->ToArray());
        self::assertSame(3, $list->Find(static fn (int $value): bool => $value === 3));
        self::assertNull($list->Find(static fn (int $value): bool => $value === 99));
        self::assertSame([1, 2, 3], $list->SortByClosure(static fn (int $left, int $right): int => $left <=> $right)->ToArray());
    }
}
