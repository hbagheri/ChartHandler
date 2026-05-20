<?php

namespace HBVSoft\ChartHandler\Tests\Rendering;

use HBVSoft\ChartHandler\Exception\UnsupportedChartTypeException;
use HBVSoft\ChartHandler\Exception\UnsupportedFormatException;
use HBVSoft\ChartHandler\Output\Format;
use HBVSoft\ChartHandler\Rendering\GdRenderer;
use HBVSoft\ChartHandler\Spec\ChartSpec;
use HBVSoft\ChartHandler\Spec\ChartType;
use HBVSoft\ChartHandler\Spec\Series;
use PHPUnit\Framework\TestCase;

class GdRendererTest extends TestCase
{
    private GdRenderer $renderer;

    protected function setUp(): void
    {
        if (! extension_loaded('gd')) {
            self::markTestSkipped('The gd extension is not loaded.');
        }
        $this->renderer = new GdRenderer();
    }

    private function pie(): ChartSpec
    {
        return new ChartSpec(
            ChartType::Pie,
            [Series::fromValues('Browsers', ['Chrome' => 60, 'Firefox' => 25, 'Safari' => 15])],
            title: 'Share',
        );
    }

    public function test_png_has_correct_signature_and_dimensions(): void
    {
        $chart = $this->renderer->render($this->pie(), Format::Png);
        $bytes = $chart->toBinary();

        self::assertSame(Format::Png, $chart->format());
        self::assertSame('image/png', $chart->mimeType());
        self::assertStringStartsWith("\x89PNG\r\n\x1a\n", $bytes);

        $size = getimagesizefromstring($bytes);
        self::assertNotFalse($size);
        self::assertSame(600, $size[0]);
        self::assertSame(400, $size[1]);
        self::assertSame(IMAGETYPE_PNG, $size[2]);

        self::assertStringStartsWith('data:image/png;base64,', $chart->toDataUri());
    }

    public function test_jpeg_signature(): void
    {
        $bytes = $this->renderer->render($this->pie(), Format::Jpeg)->toBinary();
        self::assertStringStartsWith("\xFF\xD8\xFF", $bytes);
        self::assertSame(IMAGETYPE_JPEG, (getimagesizefromstring($bytes) ?: [2 => 0])[2]);
    }

    public function test_gif_signature(): void
    {
        $bytes = $this->renderer->render($this->pie(), Format::Gif)->toBinary();
        self::assertStringStartsWith('GIF8', $bytes);
    }

    public function test_webp_signature(): void
    {
        if (! function_exists('imagewebp')) {
            self::markTestSkipped('GD was built without WebP support.');
        }
        $bytes = $this->renderer->render($this->pie(), Format::Webp)->toBinary();
        self::assertSame('RIFF', substr($bytes, 0, 4));
        self::assertSame('WEBP', substr($bytes, 8, 4));
    }

    /**
     * @return iterable<string, array{ChartType, list<Series>, list<string>}>
     */
    public static function chartTypeProvider(): iterable
    {
        yield 'donut' => [ChartType::Donut, [Series::fromValues('S', ['a' => 1, 'b' => 2, 'c' => 3])], []];
        yield 'bar' => [ChartType::Bar, [Series::fromValues('Sales', [10, 20, 30])], ['Jan', 'Feb', 'Mar']];
        yield 'line' => [ChartType::Line, [Series::fromValues('2024', [1, 2, 3]), Series::fromValues('2025', [3, 2, 1])], ['Q1', 'Q2', 'Q3']];
        yield 'area' => [ChartType::Area, [Series::fromValues('Traffic', [5, 9, 4])], ['A', 'B', 'C']];
    }

    /**
     * @param list<Series> $series
     * @param list<string> $categories
     *
     * @dataProvider chartTypeProvider
     */
    public function test_each_type_produces_a_valid_png(ChartType $type, array $series, array $categories): void
    {
        $spec = new ChartSpec($type, $series, title: 'T', categories: $categories);
        $bytes = $this->renderer->render($spec, Format::Png)->toBinary();

        $size = getimagesizefromstring($bytes);
        self::assertNotFalse($size, "{$type->value} did not produce a valid image");
        self::assertSame(600, $size[0]);
        self::assertSame(400, $size[1]);
    }

    public function test_svg_format_is_rejected(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->renderer->render($this->pie(), Format::Svg);
    }

    public function test_unsupported_type_is_rejected(): void
    {
        $spec = new ChartSpec(ChartType::Scatter, [Series::fromValues('S', [1, 2])]);
        $this->expectException(UnsupportedChartTypeException::class);
        $this->renderer->render($spec, Format::Png);
    }
}
