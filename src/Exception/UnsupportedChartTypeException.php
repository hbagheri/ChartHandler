<?php

namespace HBVSoft\ChartHandler\Exception;

use HBVSoft\ChartHandler\Spec\ChartType;
use InvalidArgumentException;

class UnsupportedChartTypeException extends InvalidArgumentException implements ChartHandlerException
{
    public static function notSupportedByRenderer(ChartType $type, string $renderer): self
    {
        return new self(sprintf(
            'Renderer "%s" does not support the "%s" chart type.',
            $renderer,
            $type->value,
        ));
    }
}
