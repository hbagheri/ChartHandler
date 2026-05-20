<?php

namespace HBVSoft\ChartHandler\Tests\Rendering\Svg;

use HBVSoft\ChartHandler\Rendering\Svg\LinearScale;
use PHPUnit\Framework\TestCase;

class LinearScaleTest extends TestCase
{
    public function test_nice_max_rounds_up_to_1_2_5_decades(): void
    {
        self::assertSame(1.0, LinearScale::niceMax(0.0));
        self::assertSame(1.0, LinearScale::niceMax(-5.0));
        self::assertSame(10.0, LinearScale::niceMax(7.0));
        self::assertSame(20.0, LinearScale::niceMax(11.0));
        self::assertSame(50.0, LinearScale::niceMax(42.0));
        self::assertSame(100.0, LinearScale::niceMax(100.0));
    }

    public function test_length_of_is_proportional(): void
    {
        $scale = new LinearScale(80.0); // niceMax = 100
        self::assertSame(100.0, $scale->max);
        self::assertEqualsWithDelta(100.0, $scale->lengthOf(50.0, 200.0), 0.001);
        self::assertEqualsWithDelta(0.0, $scale->lengthOf(0.0, 200.0), 0.001);
    }

    public function test_ticks_span_zero_to_max(): void
    {
        $ticks = (new LinearScale(80.0))->ticks(4);
        self::assertSame([0.0, 25.0, 50.0, 75.0, 100.0], $ticks);
    }
}
