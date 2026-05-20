<?php

namespace HBVSoft\ChartHandler\Spec;

use HBVSoft\ChartHandler\Exception\InvalidChartDataException;

/**
 * A named set of data points (one line, one bar group, or the slices of a pie).
 */
final class Series
{
    /** @var list<DataPoint> */
    public readonly array $points;

    /**
     * @param list<DataPoint> $points
     */
    public function __construct(
        public readonly string $name,
        array $points,
        public readonly ?string $color = null,
        public readonly ?SeriesType $type = null,
        public readonly Axis $axis = Axis::Left,
    ) {
        if ($points === []) {
            throw InvalidChartDataException::emptyPoints($name);
        }

        // Re-index to guarantee a list and reject non-DataPoint entries.
        $this->points = array_values(array_map(
            static fn (DataPoint $point): DataPoint => $point,
            $points,
        ));
    }

    /**
     * Build a series from plain values. Keys, when non-integer, become point labels:
     *   Series::fromValues('Sales', ['Q1' => 10, 'Q2' => 25])
     *   Series::fromValues('Sales', [10, 25, 40])
     *
     * @param array<int|string, int|float> $values
     */
    public static function fromValues(string $name, array $values, ?string $color = null): self
    {
        if ($values === []) {
            throw InvalidChartDataException::emptyPoints($name);
        }

        $points = [];
        foreach ($values as $label => $value) {
            $points[] = new DataPoint(
                value: (float) $value,
                label: is_string($label) ? $label : null,
            );
        }

        return new self($name, $points, $color);
    }

    /**
     * Build a series of (x, y) points for a scatter chart:
     *   Series::fromPoints('A', [[1, 5], [2, 9], [4, 3]]);
     *
     * @param list<array{0: int|float, 1: int|float}> $points
     */
    public static function fromPoints(string $name, array $points, ?string $color = null): self
    {
        if ($points === []) {
            throw InvalidChartDataException::emptyPoints($name);
        }

        $dataPoints = [];
        foreach ($points as $pair) {
            $dataPoints[] = new DataPoint(value: (float) $pair[1], x: (float) $pair[0]);
        }

        return new self($name, $dataPoints, $color);
    }

    /**
     * @return list<float>
     */
    public function values(): array
    {
        return array_map(static fn (DataPoint $p): float => $p->value, $this->points);
    }

    public function count(): int
    {
        return count($this->points);
    }

    public function withType(?SeriesType $type): self
    {
        return new self($this->name, $this->points, $this->color, $type, $this->axis);
    }

    public function withAxis(Axis $axis): self
    {
        return new self($this->name, $this->points, $this->color, $this->type, $axis);
    }
}
