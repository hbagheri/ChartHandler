<?php

namespace HBVSoft\ChartHandler\Tests;

use HBVSoft\ChartHandler\Chart;
use HBVSoft\ChartHandler\Output\Format;
use HBVSoft\ChartHandler\Rendering\GdRenderer;
use HBVSoft\ChartHandler\Rendering\SvgRenderer;
use HBVSoft\ChartHandler\Spec\ChartType;
use HBVSoft\ChartHandler\Spec\Series;
use PHPUnit\Framework\TestCase;

class StackedBarTest extends TestCase
{
    private function chart(): Chart
    {
        return Chart::stackedBar([
            Series::fromValues('Direct', [10, 20, 30]),
            Series::fromValues('Referral', [5, 15, 25]),
            Series::fromValues('Organic', [8, 12, 18]),
        ])->title('Traffic')->categories(['Q1', 'Q2', 'Q3']);
    }

    public function test_facade_builds_a_stacked_bar_spec(): void
    {
        $spec = $this->chart()->toSpec();

        self::assertSame(ChartType::StackedBar, $spec->type);
        self::assertCount(3, $spec->series);
        self::assertTrue(ChartType::StackedBar->isAxisBased());
    }

    public function test_both_backends_support_the_stacked_bar_type(): void
    {
        self::assertTrue((new SvgRenderer())->canRender(ChartType::StackedBar));
        self::assertTrue((new GdRenderer())->canRender(ChartType::StackedBar));
    }

    public function test_svg_draws_one_segment_per_series_per_category(): void
    {
        $svg = $this->chart()->toSvg();

        self::assertStringStartsWith('<svg', $svg);
        // 3 series × 3 categories = 9 stacked segments (+ background + legend swatches)
        self::assertGreaterThanOrEqual(9, substr_count($svg, '<rect'));
        self::assertStringContainsString('>Traffic</text>', $svg);
    }

    public function test_gd_stacked_bar_is_a_valid_png(): void
    {
        if (! extension_loaded('gd')) {
            self::markTestSkipped('gd not loaded');
        }

        $png = $this->chart()->toPng();
        $size = getimagesizefromstring($png);

        self::assertNotFalse($size);
        self::assertSame(600, $size[0]);
        self::assertSame(400, $size[1]);
        self::assertStringStartsWith("\x89PNG", $png);
    }

    public function test_single_series_stacked_bar_still_renders(): void
    {
        $svg = Chart::stackedBar([4, 8, 6])->categories(['a', 'b', 'c'])->toSvg();

        self::assertStringStartsWith('<svg', $svg);
        self::assertGreaterThanOrEqual(3, substr_count($svg, '<rect'));
    }
}
