<?php

namespace HBVSoft\ChartHandler\Tests\Spec;

use HBVSoft\ChartHandler\Exception\InvalidChartDataException;
use HBVSoft\ChartHandler\Spec\ChartSpec;
use HBVSoft\ChartHandler\Spec\ChartType;
use HBVSoft\ChartHandler\Spec\Series;
use HBVSoft\ChartHandler\Spec\Theme;
use PHPUnit\Framework\TestCase;

class ChartSpecTest extends TestCase
{
    public function test_builds_with_defaults(): void
    {
        $spec = new ChartSpec(
            type: ChartType::Pie,
            series: [Series::fromValues('Sales', ['A' => 10, 'B' => 20])],
            title: 'Demo',
        );

        self::assertSame(ChartType::Pie, $spec->type);
        self::assertSame('Demo', $spec->title);
        self::assertInstanceOf(Theme::class, $spec->theme);
        self::assertFalse($spec->isMultiSeries());
    }

    public function test_rejects_empty_series(): void
    {
        $this->expectException(InvalidChartDataException::class);
        new ChartSpec(type: ChartType::Bar, series: []);
    }

    public function test_rejects_series_not_matching_categories(): void
    {
        $this->expectException(InvalidChartDataException::class);
        $this->expectExceptionMessage('3 category label');

        new ChartSpec(
            type: ChartType::Bar,
            series: [Series::fromValues('S', [1, 2])],
            categories: ['Jan', 'Feb', 'Mar'],
        );
    }

    public function test_accepts_matching_categories_and_detects_multi_series(): void
    {
        $spec = new ChartSpec(
            type: ChartType::Line,
            series: [
                Series::fromValues('2024', [1, 2, 3]),
                Series::fromValues('2025', [4, 5, 6]),
            ],
            categories: ['Jan', 'Feb', 'Mar'],
        );

        self::assertTrue($spec->isMultiSeries());
        self::assertCount(3, $spec->categories);
    }
}
