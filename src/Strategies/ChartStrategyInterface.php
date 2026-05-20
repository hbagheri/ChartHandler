<?php

namespace HBVSoft\ChartHandler\Strategies;

interface ChartStrategyInterface
{
    /**
     * Render the given data into a chart representation.
     *
     * NOTE (Phase 0): strategies currently return a string placeholder. The real
     * rendering pipeline (ChartSpec + pluggable backends producing image bytes) is
     * introduced in Phase 1/2 of ROADMAP.md.
     *
     * @param array<int|string, mixed> $data
     */
    public function draw(array $data): string;
}
