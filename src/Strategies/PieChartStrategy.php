<?php

namespace HBVSoft\ChartHandler\Charts\Strategies;

class PieChartStrategy implements ChartStrategyInterface {
    public function draw(array $data): string {
        return "Rendering PieChart with data: [" . implode(',', $data) . "]";
    }
}
