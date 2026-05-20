<?php

namespace HBVSoft\ChartHandler\Tests;

use HBVSoft\ChartHandler\Chart;
use HBVSoft\ChartHandler\Output\Format;
use HBVSoft\ChartHandler\Spec\Axis;
use HBVSoft\ChartHandler\Spec\ChartType;
use HBVSoft\ChartHandler\Spec\Series;
use HBVSoft\ChartHandler\Spec\SeriesType;
use PHPUnit\Framework\TestCase;

class ComboTest extends TestCase
{
    public function test_series_withers_set_type_and_axis_immutably(): void
    {
        $base = Series::fromValues('S', [1, 2, 3]);
        self::assertNull($base->type);
        self::assertSame(Axis::Left, $base->axis);

        $derived = $base->withType(SeriesType::Bar)->withAxis(Axis::Right);
        self::assertSame(SeriesType::Bar, $derived->type);
        self::assertSame(Axis::Right, $derived->axis);

        // original is untouched
        self::assertNull($base->type);
        self::assertSame(Axis::Left, $base->axis);
    }

    public function test_facade_builds_a_combo_spec_with_per_series_type_and_axis(): void
    {
        $spec = Chart::combo()
            ->addBar('Revenue', [120, 190, 70, 220])
            ->addLine('Conversion %', [3.2, 4.1, 2.8, 5.0], Axis::Right)
            ->categories(['Q1', 'Q2', 'Q3', 'Q4'])
            ->title('Revenue vs conversion')
            ->toSpec();

        self::assertSame(ChartType::Combo, $spec->type);
        self::assertCount(2, $spec->series);
        self::assertSame(SeriesType::Bar, $spec->series[0]->type);
        self::assertSame(Axis::Left, $spec->series[0]->axis);
        self::assertSame(SeriesType::Line, $spec->series[1]->type);
        self::assertSame(Axis::Right, $spec->series[1]->axis);
    }

    private function comboChart(): Chart
    {
        return Chart::combo()
            ->addBar('Revenue', [10, 20, 30, 40])
            ->addLine('Margin %', [1, 2, 3, 4], Axis::Right)
            ->categories(['a', 'b', 'c', 'd'])
            ->title('Combo');
    }

    public function test_svg_combo_has_bars_a_line_and_a_secondary_axis(): void
    {
        $svg = $this->comboChart()->toSvg();

        self::assertStringStartsWith('<svg', $svg);
        self::assertSame(1, substr_count($svg, '<polyline'));     // the line series
        self::assertGreaterThanOrEqual(5, substr_count($svg, '<rect'));   // 4 bars + background (+legend)
        // The right axis draws 5 right-anchored… actually start-anchored tick labels:
        self::assertGreaterThanOrEqual(5, substr_count($svg, 'text-anchor="start"'));
    }

    public function test_svg_combo_without_a_right_axis_has_no_secondary_labels(): void
    {
        $svg = Chart::combo()
            ->addBar('A', [10, 20, 30])
            ->addLine('B', [5, 6, 7]) // both left axis
            ->categories(['x', 'y', 'z'])
            ->toSvg();

        self::assertSame(0, substr_count($svg, 'text-anchor="start"'));
    }

    public function test_gd_combo_produces_a_valid_png(): void
    {
        if (! extension_loaded('gd')) {
            self::markTestSkipped('gd not loaded');
        }

        $png = $this->comboChart()->toPng();
        $size = getimagesizefromstring($png);

        self::assertNotFalse($size);
        self::assertSame(600, $size[0]);
        self::assertSame(400, $size[1]);
        self::assertStringStartsWith("\x89PNG", $png);
    }

    public function test_same_combo_renders_to_svg_and_png_by_changing_one_arg(): void
    {
        $chart = $this->comboChart();
        self::assertStringStartsWith('<svg', $chart->render(Format::Svg)->toBinary());

        if (extension_loaded('gd')) {
            self::assertStringStartsWith("\x89PNG", $chart->render(Format::Png)->toBinary());
        }
    }
}
