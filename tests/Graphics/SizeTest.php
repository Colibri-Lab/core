<?php

declare(strict_types=1);

namespace Colibri\Tests\Graphics;

use Colibri\Graphics\Point;
use Colibri\Graphics\Rect;
use Colibri\Graphics\Size;
use PHPUnit\Framework\TestCase;

final class SizeTest extends TestCase
{
    public function testPresentationPropertiesAndExpansion(): void
    {
        $size = new Size(320, 240);

        self::assertSame('width:320px;height:240px;', $size->style);
        self::assertSame(' width="320" height="240"', $size->attributes);
        self::assertSame('&w=320&h=240', $size->params);
        self::assertFalse($size->isNull);

        $size->Expand(-20, 10);
        self::assertSame(300, $size->width);
        self::assertSame(250, $size->height);
    }

    public function testTransformationsPreserveAspectRatio(): void
    {
        $source = new Size(400, 200);

        $fit = $source->TransformTo(new Size(100, 100));
        self::assertSame(100, $fit->width);
        self::assertSame(50, $fit->height);

        $fill = $source->TransformToFill(new Size(100, 100));
        self::assertSame(200, $fill->width);
        self::assertSame(100, $fill->height);
    }

    public function testPointAndRectRetainAssignedCoordinates(): void
    {
        $point = new Point(4, 8);
        $rect = new Rect();
        $rect->lowerleft = $point;

        self::assertSame(4, $rect->lowerleft->x);
        self::assertSame(8, $rect->lowerleft->y);
    }
}
