<?php

namespace HBVSoft\ChartHandler\Rendering;

use HBVSoft\ChartHandler\Exception\UnsupportedChartTypeException;
use HBVSoft\ChartHandler\Exception\UnsupportedFormatException;
use HBVSoft\ChartHandler\Output\Format;
use HBVSoft\ChartHandler\Output\RenderedChart;
use HBVSoft\ChartHandler\Spec\ChartSpec;
use HBVSoft\ChartHandler\Spec\ChartType;

/**
 * A rendering backend: turns a backend-agnostic ChartSpec into output bytes of the
 * requested Format. Implementations declare which formats and chart types they handle
 * so the rest of the library (and callers) can pick a capable backend.
 *
 * Concrete backends (JpGraphRenderer, SvgRenderer, ...) arrive in Phase 2/3.
 */
interface RendererInterface
{
    /**
     * @throws UnsupportedFormatException    if $format is not in supportedFormats()
     * @throws UnsupportedChartTypeException if $spec->type is not in supportedTypes()
     */
    public function render(ChartSpec $spec, Format $format): RenderedChart;

    /**
     * @return list<Format>
     */
    public function supportedFormats(): array;

    /**
     * @return list<ChartType>
     */
    public function supportedTypes(): array;

    public function supports(Format $format): bool;

    public function canRender(ChartType $type): bool;
}
