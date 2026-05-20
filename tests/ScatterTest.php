<?php

namespace HBVSoft\ChartHandler\Tests;

use HBVSoft\ChartHandler\Chart;
use HBVSoft\ChartHandler\Rendering\GdRenderer;
use HBVSoft\ChartHandler\Rendering\SvgRenderer;
use HBVSoft\ChartHandler\Spec\ChartType;
use HBVSoft\ChartHandler\Spec\Series;
use PHPUnit\Framework\TestCase;

class ScatterTest extends TestCase
{
    public function test_series_from_points_sets_x_and_value(): void
    {
        $series = Series::fromPoints('A', [[1, 5], [2, 9], [4, 3]]);

        self::assertSame(3, $series->count());
        self::assertSame(1.0, $series->points[0]->x);
        self::assertSame(5.0, $series->points[0]->value);
        self::assertSame(4.0, $series->points[2]->x);
    }

    public function test_facade_builds_a_scatter_spec_with_multiple_series(): void
    {
        $spec = Chart::scatter([[1, 5], [2, 9]])
            ->addPoints('B', [[1, 2], [3, 6]])
            ->title('Correlation')
            ->toSpec();

        self::assertSame(ChartType::Scatter, $spec->type);
        self::assertCount(2, $spec->series);
        self::assertTrue(ChartType::Scatter->isAxisBased());
    }

    public function test_both_backends_support_scatter(): void
    {
        self::assertTrue((new SvgRenderer())->canRender(ChartType::Scatter));
        self::assertTrue((new GdRenderer())->canRender(ChartType::Scatter));
    }

    public function test_svg_scatter_draws_one_circle_per_point(): void
    {
        $svg = Chart::scatter([[1, 5], [2, 9], [4, 3], [6, 7]])
            ->title('Scatter')
            ->toSvg();

        self::assertStringStartsWith('<svg', $svg);
        self::assertSame(4, substr_count($svg, '<circle'));
        self::assertStringContainsString('>Scatter</text>', $svg);
    }

    public function test_gd_scatter_is_a_valid_png(): void
    {
        if (! extension_loaded('gd')) {
            self::markTestSkipped('gd not loaded');
        }

        $png = Chart::scatter([[1, 5], [2, 9], [4, 3]])
            ->addPoints('B', [[2, 2], [5, 7]])
            ->toPng();
        $size = getimagesizefromstring($png);

        self::assertNotFalse($size);
        self::assertSame(600, $size[0]);
        self::assertSame(400, $size[1]);
        self::assertStringStartsWith("\x89PNG", $png);
    }
}
