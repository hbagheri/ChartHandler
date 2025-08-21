<?php

namespace HBVSoft\ChartHandler;

class ChartHandler {
    protected AbstractChart $chart;

    public function __construct(AbstractChart $chart) {
        $this->chart = $chart;
    }

    public function display(): string {
        return $this->chart->render();
    }

    public function save(string $path): void {
        $binary = $this->chart->renderBinary();
        file_put_contents($path, $binary);
    }

    public function toBinary(): string {
        return $this->chart->renderBinary();
    }
}
