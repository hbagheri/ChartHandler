<?php

namespace HBVSoft\ChartHandler;

class ChartHandler
{
    protected AbstractChart $chart;

    public function __construct(AbstractChart $chart)
    {
        $this->chart = $chart;
    }

    public function display(): string
    {
        return $this->chart->render();
    }

    public function save(string $path): bool
    {
        return $this->chart->save($path);
    }

    public function toBinary(): string
    {
        return $this->chart->toBinary();
    }
}
