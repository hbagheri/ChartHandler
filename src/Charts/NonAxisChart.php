<?php

namespace HBVSoft\ChartHandler\Charts;

class NonAxisChart extends AbstractChart {
    protected \HBVSoft\Charts\Strategies\ChartStrategyInterface $strategy;

    public function __construct(\HBVSoft\Charts\Strategies\ChartStrategyInterface $strategy) {
        $this->strategy = $strategy;
    }

    public function render(): string {
        return $this->strategy->draw($this->data);
    }
}
