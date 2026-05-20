<?php

namespace HBVSoft\ChartHandler\Exception;

use InvalidArgumentException;

class InvalidChartDataException extends InvalidArgumentException implements ChartHandlerException
{
    public static function emptySeries(): self
    {
        return new self('A chart must contain at least one data series.');
    }

    public static function emptyPoints(string $series): self
    {
        return new self(sprintf('Series "%s" must contain at least one data point.', $series));
    }

    public static function categoryCountMismatch(string $series, int $expected, int $actual): self
    {
        return new self(sprintf(
            'Series "%s" has %d point(s) but the chart declares %d category label(s).',
            $series,
            $actual,
            $expected,
        ));
    }
}
