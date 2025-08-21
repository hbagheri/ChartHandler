<?php

namespace HBVSoft\ChartHandler\Charts\Strategies+-;

interface ChartStrategyInterface
{
    /**
     * @param mixed $graph   main jpgraph object
     * @param array $data   data
     * @return mixed
     *
     * this method is mandatory
     */
    public function draw(array $data): string;
}
