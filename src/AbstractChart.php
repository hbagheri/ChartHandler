<?php

namespace HBVSoft\ChartHandler;

abstract class AbstractChart
{
    protected string $title = '';

    /** @var array<int|string, mixed> */
    protected array $data = [];

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param array<int|string, mixed> $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Produce the chart output (a string placeholder in Phase 0; image bytes later).
     */
    abstract public function render(): string;

    /**
     * Binary/serialised form of the chart. Identical to render() for now; kept as a
     * distinct seam so the Phase 2 image backends can override it without touching callers.
     */
    public function toBinary(): string
    {
        return $this->render();
    }

    public function save(string $path): bool
    {
        return file_put_contents($path, $this->toBinary()) !== false;
    }
}
