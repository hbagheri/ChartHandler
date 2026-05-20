<?php

namespace HBVSoft\ChartHandler\Tests;

use HBVSoft\ChartHandler\ChartHandler;
use HBVSoft\ChartHandler\Charts\NonAxisChart;
use HBVSoft\ChartHandler\Strategies\PieChartStrategy;
use PHPUnit\Framework\TestCase;

/**
 * Phase 0 smoke test: proves the package autoloads and the facade -> chart ->
 * strategy pipeline is wired correctly end to end.
 */
class SmokeTest extends TestCase
{
    public function test_pie_chart_renders_through_the_handler(): void
    {
        $chart = (new NonAxisChart(new PieChartStrategy()))
            ->setTitle('Sales')
            ->setData([10, 20, 30]);

        $handler = new ChartHandler($chart);

        self::assertSame('Rendering PieChart with data: [10,20,30]', $handler->display());
        self::assertSame($handler->display(), $handler->toBinary());
    }

    public function test_save_writes_output_to_disk(): void
    {
        $chart = (new NonAxisChart(new PieChartStrategy()))->setData([1, 2]);
        $path = sys_get_temp_dir() . '/charthandler_smoke_' . uniqid() . '.txt';

        try {
            self::assertTrue((new ChartHandler($chart))->save($path));
            self::assertStringEqualsFile($path, 'Rendering PieChart with data: [1,2]');
        } finally {
            @unlink($path);
        }
    }
}
