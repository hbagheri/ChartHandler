<?php

namespace HBVSoft\ChartHandler\Spec;

use HBVSoft\ChartHandler\Exception\InvalidChartDataException;

/**
 * A complete, backend-agnostic description of a chart: what type it is, its data,
 * and how it should look. Chart-type builders produce a ChartSpec; renderers consume
 * one. Nothing here knows about jpgraph, SVG, GD, or any output format.
 */
final class ChartSpec
{
    /** @var list<Series> */
    public readonly array $series;

    /** @var list<string> */
    public readonly array $categories;

    public readonly Theme $theme;

    /**
     * @param list<Series> $series
     * @param list<string> $categories x-axis category labels (axis charts only)
     */
    public function __construct(
        public readonly ChartType $type,
        array $series,
        public readonly string $title = '',
        array $categories = [],
        ?Theme $theme = null,
    ) {
        if ($series === []) {
            throw InvalidChartDataException::emptySeries();
        }

        $this->series = array_values($series);
        $this->categories = array_values($categories);
        $this->theme = $theme ?? new Theme();

        $this->assertCategoriesMatch();
    }

    private function assertCategoriesMatch(): void
    {
        if ($this->categories === []) {
            return;
        }

        $expected = count($this->categories);
        foreach ($this->series as $series) {
            if ($series->count() !== $expected) {
                throw InvalidChartDataException::categoryCountMismatch(
                    $series->name,
                    $expected,
                    $series->count(),
                );
            }
        }
    }

    public function isMultiSeries(): bool
    {
        return count($this->series) > 1;
    }
}
