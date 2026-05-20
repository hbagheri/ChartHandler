<?php

namespace HBVSoft\ChartHandler\Tests\Rendering;

use HBVSoft\ChartHandler\Exception\UnsupportedFormatException;
use HBVSoft\ChartHandler\Output\Format;
use HBVSoft\ChartHandler\Rendering\SvgRenderer;
use HBVSoft\ChartHandler\Spec\ChartSpec;
use HBVSoft\ChartHandler\Spec\ChartType;
use HBVSoft\ChartHandler\Spec\Series;
use PHPUnit\Framework\TestCase;

class SvgRendererTest extends TestCase
{
    private SvgRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new SvgRenderer();
    }

    public function test_pie_renders_one_path_per_slice_with_title(): void
    {
        $spec = new ChartSpec(
            type: ChartType::Pie,
            series: [Series::fromValues('Browsers', ['Chrome' => 60, 'Firefox' => 25, 'Safari' => 15])],
            title: 'Market Share',
        );

        $svg = $this->renderer->render($spec, Format::Svg);

        self::assertSame(Format::Svg, $svg->format());
        self::assertSame('image/svg+xml', $svg->mimeType());
        self::assertStringStartsWith('<svg', $svg->toBinary());
        self::assertStringEndsWith('</svg>', $svg->toBinary());
        self::assertSame(3, substr_count($svg->toBinary(), '<path'));
        self::assertStringContainsString('>Market Share</text>', $svg->toBinary());
        self::assertStringContainsString('data:image/svg+xml;base64,', $svg->toDataUri());
    }

    public function test_single_full_slice_uses_a_circle(): void
    {
        $spec = new ChartSpec(ChartType::Pie, [Series::fromValues('Solo', ['All' => 100])]);
        $svg = $this->renderer->render($spec, Format::Svg)->toBinary();

        self::assertSame(0, substr_count($svg, '<path'));
        self::assertStringContainsString('<circle', $svg);
    }

    public function test_donut_adds_an_inner_circle(): void
    {
        $spec = new ChartSpec(ChartType::Donut, [Series::fromValues('S', ['a' => 1, 'b' => 1, 'c' => 1])]);
        $svg = $this->renderer->render($spec, Format::Svg)->toBinary();

        self::assertSame(3, substr_count($svg, '<path'));
        self::assertGreaterThanOrEqual(1, substr_count($svg, '<circle'));
    }

    public function test_bar_renders_a_rect_per_value(): void
    {
        $spec = new ChartSpec(
            type: ChartType::Bar,
            series: [Series::fromValues('Sales', [10, 20, 30])],
            categories: ['Jan', 'Feb', 'Mar'],
        );
        $svg = $this->renderer->render($spec, Format::Svg)->toBinary();

        // 1 background rect + 3 bars (single series => no legend rects)
        self::assertSame(4, substr_count($svg, '<rect'));
        self::assertStringContainsString('<line', $svg);
        self::assertStringContainsString('>Feb</text>', $svg);
    }

    public function test_line_renders_a_polyline_per_series(): void
    {
        $spec = new ChartSpec(
            type: ChartType::Line,
            series: [
                Series::fromValues('2024', [1, 2, 3]),
                Series::fromValues('2025', [3, 2, 1]),
            ],
            categories: ['Q1', 'Q2', 'Q3'],
        );
        $svg = $this->renderer->render($spec, Format::Svg)->toBinary();

        self::assertSame(2, substr_count($svg, '<polyline'));
    }

    public function test_area_renders_a_filled_polygon(): void
    {
        $spec = new ChartSpec(
            type: ChartType::Area,
            series: [Series::fromValues('Traffic', [5, 9, 4])],
            categories: ['A', 'B', 'C'],
        );
        $svg = $this->renderer->render($spec, Format::Svg)->toBinary();

        self::assertStringContainsString('<polygon', $svg);
        self::assertStringContainsString('fill-opacity="0.2"', $svg);
    }

    public function test_unsupported_format_is_rejected(): void
    {
        // SvgRenderer only emits SVG; asking for a raster format must be rejected.
        $spec = new ChartSpec(ChartType::Pie, [Series::fromValues('S', [1, 2])]);

        $this->expectException(UnsupportedFormatException::class);
        $this->renderer->render($spec, Format::Png);
    }

    public function test_malicious_label_is_escaped(): void
    {
        $spec = new ChartSpec(
            ChartType::Pie,
            [Series::fromValues('S', ['<script>' => 1, 'ok' => 1])],
        );
        $svg = $this->renderer->render($spec, Format::Svg)->toBinary();

        self::assertStringNotContainsString('<script>', $svg);
        self::assertStringContainsString('&lt;script&gt;', $svg);
    }
}
