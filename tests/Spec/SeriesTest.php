<?php

namespace HBVSoft\ChartHandler\Tests\Spec;

use HBVSoft\ChartHandler\Exception\InvalidChartDataException;
use HBVSoft\ChartHandler\Spec\ChartType;
use HBVSoft\ChartHandler\Spec\DataPoint;
use HBVSoft\ChartHandler\Spec\Series;
use PHPUnit\Framework\TestCase;

class SeriesTest extends TestCase
{
    public function test_from_values_uses_string_keys_as_labels(): void
    {
        $series = Series::fromValues('Sales', ['Q1' => 10, 'Q2' => 25]);

        self::assertSame([10.0, 25.0], $series->values());
        self::assertSame(2, $series->count());
        self::assertSame('Q1', $series->points[0]->label);
        self::assertNull(Series::fromValues('S', [5, 6])->points[0]->label);
    }

    public function test_empty_values_are_rejected(): void
    {
        $this->expectException(InvalidChartDataException::class);
        Series::fromValues('Empty', []);
    }

    public function test_constructor_reindexes_points_to_a_list(): void
    {
        $series = new Series('S', [3 => new DataPoint(1.0), 9 => new DataPoint(2.0)]);

        self::assertSame([1.0, 2.0], $series->values());
    }

    public function test_chart_type_axis_classification(): void
    {
        self::assertFalse(ChartType::Pie->isAxisBased());
        self::assertTrue(ChartType::Bar->isAxisBased());
        self::assertFalse(ChartType::Donut->supportsMultipleSeries());
        self::assertTrue(ChartType::Line->supportsMultipleSeries());
    }
}
