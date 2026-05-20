<?php

namespace HBVSoft\ChartHandler\Charts;

use HBVSoft\ChartHandler\AbstractChart;
use HBVSoft\ChartHandler\Strategies\ChartStrategyInterface;

class NonAxisChart extends AbstractChart
{
    protected ChartStrategyInterface $strategy;

    public function __construct(ChartStrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    public function render(): string
    {
        return $this->strategy->draw($this->data);
    }
}
