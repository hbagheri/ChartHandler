<?php
namespace HBVSoft\ChartHandler;

use HBVSoft\Charts\Strategies\ChartStrategyInterface;

abstract class AbstractChart
{
    protected ChartStrategyInterface $strategy;
    protected string $title = '';
    protected array $data = [];



    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function save(string $path): bool
    {
        $content = $this->toBinary();
        return (bool) file_put_contents($path, $content);
    }

    public function toBinary(): string
    {
        return $this->render();
    }

    abstract public function render();

    public function renderBinary(): string {
        return (string) $this->render();

    }
}
