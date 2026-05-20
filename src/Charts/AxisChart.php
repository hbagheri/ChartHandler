<?php

namespace HBVSoft\ChartHandler\Charts;

use HBVSoft\ChartHandler\AbstractChart;

abstract class AxisChart extends AbstractChart
{
    /** @var array<int, string> */
    protected array $labels = [];

    /**
     * @param array<int, string> $labels
     */
    public function setLabels(array $labels): static
    {
        $this->labels = $labels;

        return $this;
    }
}
