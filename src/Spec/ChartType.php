<?php

namespace HBVSoft\ChartHandler\Spec;

/**
 * The supported chart types.
 *
 * Adding a new type is the documented extension point: add a case here, then teach
 * each renderer how to draw it (see ROADMAP.md Phase 4). Axis-based types share the
 * category/axis machinery; non-axis types (pie/donut) ignore categories.
 */
enum ChartType: string
{
    case Pie = 'pie';
    case Donut = 'donut';
    case Bar = 'bar';
    case StackedBar = 'stacked_bar';
    case Line = 'line';
    case Area = 'area';
    case Scatter = 'scatter';
    case Combo = 'combo';

    public function isAxisBased(): bool
    {
        return match ($this) {
            self::Pie, self::Donut => false,
            default => true,
        };
    }

    /**
     * Whether this type draws multiple series on the same plot (line/area/bar groups).
     * Pie and donut render a single series.
     */
    public function supportsMultipleSeries(): bool
    {
        return match ($this) {
            self::Pie, self::Donut => false,
            default => true,
        };
    }
}
