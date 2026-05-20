<?php

namespace HBVSoft\ChartHandler\Tests\Rendering;

use HBVSoft\ChartHandler\Output\Format;
use HBVSoft\ChartHandler\Output\RenderedChart;
use HBVSoft\ChartHandler\Rendering\AbstractRenderer;
use HBVSoft\ChartHandler\Spec\ChartSpec;
use HBVSoft\ChartHandler\Spec\ChartType;

/**
 * Minimal in-memory renderer used to test the capability/guard logic in
 * AbstractRenderer without depending on any image library.
 */
final class FakeRenderer extends AbstractRenderer
{
    public function supportedFormats(): array
    {
        return [Format::Png, Format::Svg];
    }

    public function supportedTypes(): array
    {
        return [ChartType::Pie, ChartType::Bar];
    }

    protected function doRender(ChartSpec $spec, Format $format): RenderedChart
    {
        return new RenderedChart(
            sprintf('fake:%s:%s', $spec->type->value, $format->value),
            $format,
        );
    }
}
