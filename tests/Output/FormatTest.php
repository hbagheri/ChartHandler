<?php

namespace HBVSoft\ChartHandler\Tests\Output;

use HBVSoft\ChartHandler\Exception\ChartHandlerException;
use HBVSoft\ChartHandler\Exception\UnsupportedFormatException;
use HBVSoft\ChartHandler\Output\Format;
use PHPUnit\Framework\TestCase;

class FormatTest extends TestCase
{
    public function test_mime_types(): void
    {
        self::assertSame('image/png', Format::Png->mimeType());
        self::assertSame('image/jpeg', Format::Jpeg->mimeType());
        self::assertSame('image/svg+xml', Format::Svg->mimeType());
    }

    public function test_jpeg_extension_is_jpg(): void
    {
        self::assertSame('jpg', Format::Jpeg->extension());
        self::assertSame('png', Format::Png->extension());
    }

    public function test_vector_vs_raster(): void
    {
        self::assertTrue(Format::Svg->isVector());
        self::assertFalse(Format::Svg->isRaster());
        self::assertTrue(Format::Png->isRaster());
    }

    public function test_from_extension_is_case_and_dot_insensitive(): void
    {
        self::assertSame(Format::Jpeg, Format::fromExtension('.JPG'));
        self::assertSame(Format::Jpeg, Format::fromExtension('image/jpeg'));
        self::assertSame(Format::Svg, Format::fromExtension('svg'));
    }

    public function test_from_extension_rejects_unknown(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->expectException(ChartHandlerException::class);
        Format::fromExtension('bmp');
    }
}
