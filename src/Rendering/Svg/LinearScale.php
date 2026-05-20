<?php

namespace HBVSoft\ChartHandler\Rendering\Svg;

/**
 * Maps data values from [0 .. niceMax] onto pixel coordinates on an axis, choosing a
 * human-friendly upper bound (1/2/5 x 10^n) so gridline labels read cleanly.
 */
final class LinearScale
{
    public readonly float $max;

    public function __construct(float $dataMax)
    {
        $this->max = self::niceMax($dataMax);
    }

    public static function niceMax(float $dataMax): float
    {
        if ($dataMax <= 0.0) {
            return 1.0;
        }

        $exponent = floor(log10($dataMax));
        $magnitude = 10 ** $exponent;
        $fraction = $dataMax / $magnitude;

        $niceFraction = match (true) {
            $fraction <= 1.0 => 1.0,
            $fraction <= 2.0 => 2.0,
            $fraction <= 5.0 => 5.0,
            default => 10.0,
        };

        return $niceFraction * $magnitude;
    }

    /**
     * Pixel length (from the baseline) representing $value across $pixelSpan.
     */
    public function lengthOf(float $value, float $pixelSpan): float
    {
        return ($value / $this->max) * $pixelSpan;
    }

    /**
     * @return list<float> evenly spaced tick values from 0 to max inclusive
     */
    public function ticks(int $count = 4): array
    {
        $count = max(1, $count);
        $ticks = [];
        for ($i = 0; $i <= $count; $i++) {
            $ticks[] = $this->max * $i / $count;
        }

        return $ticks;
    }
}
