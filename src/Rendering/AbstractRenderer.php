<?php

namespace HBVSoft\ChartHandler\Rendering;

use HBVSoft\ChartHandler\Exception\UnsupportedChartTypeException;
use HBVSoft\ChartHandler\Exception\UnsupportedFormatException;
use HBVSoft\ChartHandler\Output\Format;
use HBVSoft\ChartHandler\Output\RenderedChart;
use HBVSoft\ChartHandler\Spec\ChartSpec;
use HBVSoft\ChartHandler\Spec\ChartType;

/**
 * Base class that implements the capability bookkeeping and the
 * format/type guard, leaving concrete backends to implement only the actual drawing
 * in doRender().
 */
abstract class AbstractRenderer implements RendererInterface
{
    public function render(ChartSpec $spec, Format $format): RenderedChart
    {
        if (! $this->supports($format)) {
            throw UnsupportedFormatException::notSupportedByRenderer($format, static::class);
        }

        if (! $this->canRender($spec->type)) {
            throw UnsupportedChartTypeException::notSupportedByRenderer($spec->type, static::class);
        }

        return $this->doRender($spec, $format);
    }

    public function supports(Format $format): bool
    {
        return in_array($format, $this->supportedFormats(), true);
    }

    public function canRender(ChartType $type): bool
    {
        return in_array($type, $this->supportedTypes(), true);
    }

    /**
     * Draw the chart. Callers reach this only after format/type have been validated.
     */
    abstract protected function doRender(ChartSpec $spec, Format $format): RenderedChart;
}
