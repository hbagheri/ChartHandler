<?php

namespace HBVSoft\ChartHandler\Spec;

/**
 * A single value in a series, optionally carrying its own label and color
 * (per-slice labels/colors matter for pie/donut charts). `$x` holds the numeric
 * x-coordinate for scatter charts; it is null for category-based chart types.
 */
final class DataPoint
{
    public function __construct(
        public readonly float $value,
        public readonly ?string $label = null,
        public readonly ?string $color = null,
        public readonly ?float $x = null,
    ) {
    }
}
