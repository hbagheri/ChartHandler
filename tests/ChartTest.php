<?php

namespace HBVSoft\ChartHandler\Tests;

use HBVSoft\ChartHandler\Chart;
use HBVSoft\ChartHandler\Exception\InvalidChartDataException;
use HBVSoft\ChartHandler\Output\Format;
use HBVSoft\ChartHandler\Rendering\RendererRegistry;
use HBVSoft\ChartHandler\Spec\ChartType;
use HBVSoft\ChartHandler\Spec\LegendPosition;
use HBVSoft\ChartHandler\Spec\Series;
use HBVSoft\ChartHandler\Tests\Rendering\FakeRenderer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ChartTest extends TestCase
{
    public function test_pie_from_assoc_builds_a_spec_and_renders_svg(): void
    {
        $svg = Chart::pie(['Chrome' => 60, 'Firefox' => 25, 'Safari' => 15])
            ->title('Share')
            ->toSvg();

        self::assertStringStartsWith('<svg', $svg);
        self::assertSame(3, substr_count($svg, '<path'));
        self::assertStringContainsString('>Share</text>', $svg);
    }

    public function test_to_spec_reflects_fluent_configuration(): void
    {
        $spec = Chart::bar([1, 2, 3])
            ->title('T')
            ->categories(['a', 'b', 'c'])
            ->size(800, 300)
            ->legend(LegendPosition::None)
            ->palette(['#111111'])
            ->toSpec();

        self::assertSame(ChartType::Bar, $spec->type);
        self::assertSame('T', $spec->title);
        self::assertSame(['a', 'b', 'c'], $spec->categories);
        self::assertSame(800, $spec->theme->width);
        self::assertSame(300, $spec->theme->height);
        self::assertSame(LegendPosition::None, $spec->theme->legend);
        self::assertSame('#111111', $spec->theme->colorAt(0));
    }

    public function test_line_accepts_a_list_of_series_and_add_series(): void
    {
        $spec = Chart::line([Series::fromValues('2024', [1, 2, 3])])
            ->addSeries(Series::fromValues('2025', [3, 2, 1]))
            ->categories(['Q1', 'Q2', 'Q3'])
            ->toSpec();

        self::assertTrue($spec->isMultiSeries());
        self::assertCount(2, $spec->series);
    }

    public function test_dispatch_uses_the_registry(): void
    {
        // FakeRenderer supports Svg + Pie; inject it as the SVG backend.
        $registry = new RendererRegistry(svg: new FakeRenderer());

        $out = Chart::pie(['a' => 1, 'b' => 1])
            ->renderers($registry)
            ->render(Format::Svg);

        self::assertSame('fake:pie:svg', $out->toBinary());
    }

    public function test_email_img_is_a_png_data_uri(): void
    {
        if (! extension_loaded('gd')) {
            self::markTestSkipped('gd not loaded');
        }

        $html = Chart::pie(['a' => 1, 'b' => 2])->toEmailImg(['alt' => 'chart']);

        self::assertStringStartsWith('<img src="data:image/png;base64,', $html);
        self::assertStringContainsString('alt="chart"', $html);
    }

    public function test_save_infers_format_from_extension(): void
    {
        $svgPath = sys_get_temp_dir() . '/chart_' . uniqid() . '.svg';
        try {
            self::assertTrue(Chart::pie(['a' => 1, 'b' => 1])->save($svgPath));
            self::assertStringStartsWith('<svg', (string) file_get_contents($svgPath));
        } finally {
            @unlink($svgPath);
        }
    }

    public function test_empty_data_is_rejected(): void
    {
        $this->expectException(InvalidChartDataException::class);
        Chart::pie([]);
    }

    public function test_mixing_series_and_scalars_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Chart::bar([Series::fromValues('a', [1, 2]), 5]);
    }
}
