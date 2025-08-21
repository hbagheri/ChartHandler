<?php
namespace HBVSoft\ChartHandler\Charts;

abstract class AxisChart extends AbstractChart
{
    protected array $labels = [];

    public function setLabels(array $labels): static
    {
        $this->labels = $labels;
        return $this;
    }

}
