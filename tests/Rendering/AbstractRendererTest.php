<?php

namespace HBVSoft\ChartHandler\Tests\Rendering;

use HBVSoft\ChartHandler\Exception\UnsupportedChartTypeException;
use HBVSoft\ChartHandler\Exception\UnsupportedFormatException;
use HBVSoft\ChartHandler\Output\Format;
use HBVSoft\ChartHandler\Spec\ChartSpec;
use HBVSoft\ChartHandler\Spec\ChartType;
use HBVSoft\ChartHandler\Spec\Series;
use PHPUnit\Framework\TestCase;

class AbstractRendererTest extends TestCase
{
    private function pie(): ChartSpec
    {
        return new ChartSpec(ChartType::Pie, [Series::fromValues('S', [1, 2, 3])]);
    }

    public function test_capability_reporting(): void
    {
        $renderer = new FakeRenderer();

        self::assertTrue($renderer->supports(Format::Png));
        self::assertFalse($renderer->supports(Format::Jpeg));
        self::assertTrue($renderer->canRender(ChartType::Bar));
        self::assertFalse($renderer->canRender(ChartType::Scatter));
    }

    public function test_render_dispatches_to_do_render_when_supported(): void
    {
        $result = (new FakeRenderer())->render($this->pie(), Format::Svg);

        self::assertSame('fake:pie:svg', $result->toBinary());
        self::assertSame(Format::Svg, $result->format());
    }

    public function test_render_rejects_unsupported_format(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        (new FakeRenderer())->render($this->pie(), Format::Jpeg);
    }

    public function test_render_rejects_unsupported_type(): void
    {
        $spec = new ChartSpec(ChartType::Scatter, [Series::fromValues('S', [1, 2])]);

        $this->expectException(UnsupportedChartTypeException::class);
        (new FakeRenderer())->render($spec, Format::Png);
    }
}
