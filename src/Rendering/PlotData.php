<?php

namespace HBVSoft\ChartHandler\Rendering;

use HBVSoft\ChartHandler\Spec\ChartSpec;

/**
 * Pure helpers shared by the rendering backends to read normalized data out of a
 * ChartSpec the same way (so SVG and GD agree on categories and value range).
 */
final class PlotData
{
    /**
     * Category (x-axis) labels: explicit spec categories, else the first series' point
     * labels, else 1-based indices.
     *
     * @return list<string>
     */
    public static function categories(ChartSpec $spec): array
    {
        if ($spec->categories !== []) {
            return $spec->categories;
        }

        $labels = [];
        foreach ($spec->series[0]->points as $i => $point) {
            $labels[] = $point->label ?? (string) ($i + 1);
        }

        return $labels;
    }

    /**
     * Largest value across every series (never below 0; the value axis starts at 0).
     */
    public static function maxValue(ChartSpec $spec): float
    {
        $max = 0.0;
        foreach ($spec->series as $series) {
            foreach ($series->points as $point) {
                $max = max($max, $point->value);
            }
        }

        return $max;
    }
}
