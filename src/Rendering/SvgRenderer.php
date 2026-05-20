<?php

namespace HBVSoft\ChartHandler\Rendering;

use HBVSoft\ChartHandler\Output\Format;
use HBVSoft\ChartHandler\Output\RenderedChart;
use HBVSoft\ChartHandler\Rendering\Svg\LinearScale;
use HBVSoft\ChartHandler\Rendering\Svg\SvgCanvas;
use HBVSoft\ChartHandler\Spec\Axis;
use HBVSoft\ChartHandler\Spec\ChartSpec;
use HBVSoft\ChartHandler\Spec\ChartType;
use HBVSoft\ChartHandler\Spec\SeriesType;

/**
 * Pure-PHP backend that renders a ChartSpec to SVG markup — no GD, no external
 * libraries. SVG is text, so it embeds directly as a base64 data-URI for offline
 * HTML emails. Supports pie/donut (non-axis) and bar/line/area (axis) charts.
 */
final class SvgRenderer extends AbstractRenderer
{
    public function supportedFormats(): array
    {
        return [Format::Svg];
    }

    public function supportedTypes(): array
    {
        return [
            ChartType::Pie,
            ChartType::Donut,
            ChartType::Bar,
            ChartType::StackedBar,
            ChartType::Line,
            ChartType::Area,
            ChartType::Combo,
            ChartType::Scatter,
        ];
    }

    protected function doRender(ChartSpec $spec, Format $format): RenderedChart
    {
        $theme = $spec->theme;
        $canvas = new SvgCanvas($theme->width, $theme->height, $theme->background, $theme->fontFamily);

        match ($spec->type) {
            ChartType::Pie => $this->drawPie($canvas, $spec, false),
            ChartType::Donut => $this->drawPie($canvas, $spec, true),
            ChartType::Bar => $this->drawBars($canvas, $spec),
            ChartType::StackedBar => $this->drawStackedBar($canvas, $spec),
            ChartType::Line => $this->drawLines($canvas, $spec, false),
            ChartType::Area => $this->drawLines($canvas, $spec, true),
            ChartType::Combo => $this->drawCombo($canvas, $spec),
            ChartType::Scatter => $this->drawScatter($canvas, $spec),
        };

        if ($spec->title !== '') {
            $canvas->text((float) $theme->width / 2, 20, $spec->title, [
                'text-anchor' => 'middle',
                'font-size' => '16',
                'font-weight' => 'bold',
                'fill' => '#333',
            ]);
        }

        return new RenderedChart($canvas->render($spec->title), Format::Svg);
    }

    // --- Non-axis: pie & donut --------------------------------------------------

    private function drawPie(SvgCanvas $canvas, ChartSpec $spec, bool $donut): void
    {
        $theme = $spec->theme;
        $points = $spec->series[0]->points;
        $total = 0.0;
        foreach ($points as $point) {
            $total += abs($point->value);
        }
        if ($total <= 0.0) {
            return;
        }

        $top = $spec->title !== '' ? 28.0 : 8.0;
        $legendVisible = $theme->legend->isVisible();
        $legendWidth = $legendVisible ? 150.0 : 0.0;
        $areaWidth = $theme->width - $legendWidth;
        $areaHeight = $theme->height - $top;
        $radius = max(10.0, min($areaWidth, $areaHeight) / 2 - 16.0);
        $cx = $areaWidth / 2;
        $cy = $top + $areaHeight / 2;

        /** @var list<array{label: string, color: string}> $legend */
        $legend = [];
        $startDeg = -90.0;
        foreach ($points as $i => $point) {
            $color = $point->color ?? $theme->colorAt($i);
            $sweep = (abs($point->value) / $total) * 360.0;

            if ($sweep >= 359.999) {
                $canvas->circle($cx, $cy, $radius, $color, 'stroke="#ffffff" stroke-width="1"');
            } else {
                $canvas->add($this->slicePath($cx, $cy, $radius, $startDeg, $startDeg + $sweep, $color));
            }
            $startDeg += $sweep;

            $legend[] = ['label' => $point->label ?? 'Item ' . ($i + 1), 'color' => $color];
        }

        if ($donut) {
            $canvas->circle($cx, $cy, $radius * 0.55, $theme->background ?? '#ffffff');
        }

        if ($legendVisible) {
            $this->drawLegend($canvas, $legend, $theme->width - $legendWidth + 12.0, $top + 12.0);
        }
    }

    private function slicePath(float $cx, float $cy, float $r, float $a0, float $a1, string $fill): string
    {
        $x1 = $cx + $r * cos(deg2rad($a0));
        $y1 = $cy + $r * sin(deg2rad($a0));
        $x2 = $cx + $r * cos(deg2rad($a1));
        $y2 = $cy + $r * sin(deg2rad($a1));
        $largeArc = ($a1 - $a0) > 180.0 ? 1 : 0;

        return sprintf(
            '<path d="M %s %s L %s %s A %s %s 0 %d 1 %s %s Z" fill="%s" stroke="#ffffff" stroke-width="1" />',
            SvgCanvas::num($cx),
            SvgCanvas::num($cy),
            SvgCanvas::num($x1),
            SvgCanvas::num($y1),
            SvgCanvas::num($r),
            SvgCanvas::num($r),
            $largeArc,
            SvgCanvas::num($x2),
            SvgCanvas::num($y2),
            SvgCanvas::esc($fill),
        );
    }

    // --- Axis: bars -------------------------------------------------------------

    private function drawBars(SvgCanvas $canvas, ChartSpec $spec): void
    {
        $plot = $this->beginPlot($canvas, $spec);
        $categories = PlotData::categories($spec);
        $count = count($categories);
        if ($count === 0) {
            return;
        }

        $scale = new LinearScale(PlotData::maxValue($spec));
        $this->drawAxes($canvas, $plot, $scale);

        $seriesCount = max(1, count($spec->series));
        $groupWidth = $plot['w'] / $count;
        $bandWidth = $groupWidth * 0.8;
        $barWidth = $bandWidth / $seriesCount;
        $baseline = $plot['y'] + $plot['h'];

        foreach ($spec->series as $si => $series) {
            $color = $series->color ?? $spec->theme->colorAt($si);
            foreach ($series->points as $ci => $point) {
                if ($ci >= $count) {
                    break;
                }
                $height = $scale->lengthOf(max(0.0, $point->value), $plot['h']);
                $groupX = $plot['x'] + $ci * $groupWidth + ($groupWidth - $bandWidth) / 2;
                $canvas->rect($groupX + $si * $barWidth, $baseline - $height, max(0.0, $barWidth - 1.0), $height, $color);
            }
        }

        $this->drawCategoryLabels($canvas, $categories, $plot, $groupWidth);
        $this->maybeDrawSeriesLegend($canvas, $spec, $plot);
    }

    // --- Axis: lines & areas ----------------------------------------------------

    private function drawLines(SvgCanvas $canvas, ChartSpec $spec, bool $area): void
    {
        $plot = $this->beginPlot($canvas, $spec);
        $categories = PlotData::categories($spec);
        $count = count($categories);
        if ($count === 0) {
            return;
        }

        $scale = new LinearScale(PlotData::maxValue($spec));
        $this->drawAxes($canvas, $plot, $scale);

        $groupWidth = $plot['w'] / $count;
        $baseline = $plot['y'] + $plot['h'];

        foreach ($spec->series as $si => $series) {
            $color = $series->color ?? $spec->theme->colorAt($si);

            /** @var list<array{x: float, y: float}> $pts */
            $pts = [];
            foreach ($series->points as $ci => $point) {
                if ($ci >= $count) {
                    break;
                }
                $pts[] = [
                    'x' => $plot['x'] + $ci * $groupWidth + $groupWidth / 2,
                    'y' => $baseline - $scale->lengthOf(max(0.0, $point->value), $plot['h']),
                ];
            }
            if ($pts === []) {
                continue;
            }

            $polyline = implode(' ', array_map(
                static fn (array $p): string => SvgCanvas::num($p['x']) . ',' . SvgCanvas::num($p['y']),
                $pts,
            ));

            if ($area) {
                $polygon = SvgCanvas::num($pts[0]['x']) . ',' . SvgCanvas::num($baseline) . ' '
                    . $polyline . ' '
                    . SvgCanvas::num($pts[count($pts) - 1]['x']) . ',' . SvgCanvas::num($baseline);
                $canvas->add(sprintf(
                    '<polygon points="%s" fill="%s" fill-opacity="0.2" stroke="none" />',
                    $polygon,
                    SvgCanvas::esc($color),
                ));
            }

            $canvas->add(sprintf(
                '<polyline points="%s" fill="none" stroke="%s" stroke-width="2" />',
                $polyline,
                SvgCanvas::esc($color),
            ));

            foreach ($pts as $p) {
                $canvas->circle($p['x'], $p['y'], 3.0, $color);
            }
        }

        $this->drawCategoryLabels($canvas, $categories, $plot, $groupWidth);
        $this->maybeDrawSeriesLegend($canvas, $spec, $plot);
    }

    // --- Axis: stacked bars -----------------------------------------------------

    private function drawStackedBar(SvgCanvas $canvas, ChartSpec $spec): void
    {
        $categories = PlotData::categories($spec);
        $count = count($categories);
        if ($count === 0) {
            return;
        }

        $scale = new LinearScale($this->stackedMax($spec, $count));
        $plot = $this->beginPlot($canvas, $spec);
        $this->drawAxes($canvas, $plot, $scale);

        $baseline = $plot['y'] + $plot['h'];
        $groupWidth = $plot['w'] / $count;
        $barWidth = $groupWidth * 0.6;

        for ($ci = 0; $ci < $count; $ci++) {
            $cumulative = 0.0;
            $bx = $plot['x'] + $ci * $groupWidth + ($groupWidth - $barWidth) / 2;
            foreach ($spec->series as $si => $series) {
                if ($ci >= $series->count()) {
                    continue;
                }
                $value = max(0.0, $series->points[$ci]->value);
                $yBottom = $baseline - $scale->lengthOf($cumulative, $plot['h']);
                $yTop = $baseline - $scale->lengthOf($cumulative + $value, $plot['h']);
                $hex = $series->color ?? $spec->theme->colorAt($si);
                $canvas->rect($bx, $yTop, $barWidth, max(0.0, $yBottom - $yTop), $hex);
                $cumulative += $value;
            }
        }

        $this->drawCategoryLabels($canvas, $categories, $plot, $groupWidth);
        $this->maybeDrawSeriesLegend($canvas, $spec, $plot);
    }

    /**
     * Largest stacked total across categories (the value-axis ceiling for a stacked bar).
     */
    private function stackedMax(ChartSpec $spec, int $count): float
    {
        $max = 0.0;
        for ($ci = 0; $ci < $count; $ci++) {
            $sum = 0.0;
            foreach ($spec->series as $series) {
                if ($ci < $series->count()) {
                    $sum += max(0.0, $series->points[$ci]->value);
                }
            }
            $max = max($max, $sum);
        }

        return $max;
    }

    // --- Combo: mixed series with an optional secondary axis --------------------

    private function drawCombo(SvgCanvas $canvas, ChartSpec $spec): void
    {
        $categories = PlotData::categories($spec);
        $count = count($categories);
        if ($count === 0) {
            return;
        }

        [$leftScale, $rightScale] = $this->comboScales($spec);
        $plot = $this->beginPlot($canvas, $spec, $rightScale !== null);
        $this->drawComboAxes($canvas, $plot, $leftScale, $rightScale);

        $baseline = $plot['y'] + $plot['h'];
        $groupWidth = $plot['w'] / $count;

        $barCount = 0;
        foreach ($spec->series as $series) {
            if (($series->type ?? SeriesType::Line) === SeriesType::Bar) {
                $barCount++;
            }
        }
        $bandWidth = $groupWidth * 0.8;
        $barWidth = $bandWidth / max(1, $barCount);

        $barSlot = 0;
        foreach ($spec->series as $si => $series) {
            $type = $series->type ?? SeriesType::Line;
            $scale = ($series->axis === Axis::Right && $rightScale !== null) ? $rightScale : $leftScale;
            $hex = $series->color ?? $spec->theme->colorAt($si);

            if ($type === SeriesType::Bar) {
                foreach ($series->points as $ci => $point) {
                    if ($ci >= $count) {
                        break;
                    }
                    $height = $scale->lengthOf(max(0.0, $point->value), $plot['h']);
                    $bx = $plot['x'] + $ci * $groupWidth + ($groupWidth - $bandWidth) / 2 + $barSlot * $barWidth;
                    $canvas->rect($bx, $baseline - $height, max(0.0, $barWidth - 1.0), $height, $hex);
                }
                $barSlot++;

                continue;
            }

            /** @var list<array{x: float, y: float}> $pts */
            $pts = [];
            foreach ($series->points as $ci => $point) {
                if ($ci >= $count) {
                    break;
                }
                $pts[] = [
                    'x' => $plot['x'] + $ci * $groupWidth + $groupWidth / 2,
                    'y' => $baseline - $scale->lengthOf(max(0.0, $point->value), $plot['h']),
                ];
            }
            if ($pts === []) {
                continue;
            }

            $polyline = implode(' ', array_map(
                static fn (array $p): string => SvgCanvas::num($p['x']) . ',' . SvgCanvas::num($p['y']),
                $pts,
            ));

            if ($type === SeriesType::Area) {
                $polygon = SvgCanvas::num($pts[0]['x']) . ',' . SvgCanvas::num($baseline) . ' '
                    . $polyline . ' '
                    . SvgCanvas::num($pts[count($pts) - 1]['x']) . ',' . SvgCanvas::num($baseline);
                $canvas->add(sprintf('<polygon points="%s" fill="%s" fill-opacity="0.2" stroke="none" />', $polygon, SvgCanvas::esc($hex)));
            }

            $canvas->add(sprintf('<polyline points="%s" fill="none" stroke="%s" stroke-width="2" />', $polyline, SvgCanvas::esc($hex)));
            foreach ($pts as $p) {
                $canvas->circle($p['x'], $p['y'], 3.0, $hex);
            }
        }

        $this->drawCategoryLabels($canvas, $categories, $plot, $groupWidth);
        $this->maybeDrawSeriesLegend($canvas, $spec, $plot);
    }

    /**
     * @return array{0: LinearScale, 1: LinearScale|null}
     */
    private function comboScales(ChartSpec $spec): array
    {
        $leftMax = 0.0;
        $rightMax = 0.0;
        $hasRight = false;
        foreach ($spec->series as $series) {
            foreach ($series->points as $point) {
                if ($series->axis === Axis::Right) {
                    $rightMax = max($rightMax, $point->value);
                    $hasRight = true;
                } else {
                    $leftMax = max($leftMax, $point->value);
                }
            }
        }

        return [new LinearScale($leftMax), $hasRight ? new LinearScale($rightMax) : null];
    }

    /**
     * @param array{x: float, y: float, w: float, h: float} $plot
     */
    private function drawComboAxes(SvgCanvas $canvas, array $plot, LinearScale $left, ?LinearScale $right): void
    {
        $baseline = $plot['y'] + $plot['h'];

        for ($i = 0; $i <= 4; $i++) {
            $fraction = $i / 4;
            $y = $baseline - $fraction * $plot['h'];
            $canvas->line($plot['x'], $y, $plot['x'] + $plot['w'], $y, '#e5e5e5');
            $canvas->text($plot['x'] - 6.0, $y + 4.0, SvgCanvas::num($left->max * $fraction), [
                'font-size' => '11', 'fill' => '#666', 'text-anchor' => 'end',
            ]);
            if ($right !== null) {
                $canvas->text($plot['x'] + $plot['w'] + 6.0, $y + 4.0, SvgCanvas::num($right->max * $fraction), [
                    'font-size' => '11', 'fill' => '#666', 'text-anchor' => 'start',
                ]);
            }
        }

        $canvas->line($plot['x'], $plot['y'], $plot['x'], $baseline, '#999999');
        $canvas->line($plot['x'], $baseline, $plot['x'] + $plot['w'], $baseline, '#999999');
        if ($right !== null) {
            $canvas->line($plot['x'] + $plot['w'], $plot['y'], $plot['x'] + $plot['w'], $baseline, '#999999');
        }
    }

    // --- Scatter (numeric x / y) ------------------------------------------------

    private function drawScatter(SvgCanvas $canvas, ChartSpec $spec): void
    {
        $xScale = new LinearScale($this->scatterMaxX($spec));
        $yScale = new LinearScale(PlotData::maxValue($spec));
        $plot = $this->beginPlot($canvas, $spec);
        $this->drawScatterAxes($canvas, $plot, $xScale, $yScale);

        $baseline = $plot['y'] + $plot['h'];
        foreach ($spec->series as $si => $series) {
            $hex = $series->color ?? $spec->theme->colorAt($si);
            foreach ($series->points as $idx => $point) {
                $x = $point->x ?? (float) $idx;
                $px = $plot['x'] + $xScale->lengthOf($x, $plot['w']);
                $py = $baseline - $yScale->lengthOf(max(0.0, $point->value), $plot['h']);
                $canvas->circle($px, $py, 4.0, $hex);
            }
        }

        $this->maybeDrawSeriesLegend($canvas, $spec, $plot);
    }

    /**
     * @param array{x: float, y: float, w: float, h: float} $plot
     */
    private function drawScatterAxes(SvgCanvas $canvas, array $plot, LinearScale $xScale, LinearScale $yScale): void
    {
        $baseline = $plot['y'] + $plot['h'];

        for ($i = 0; $i <= 4; $i++) {
            $fraction = $i / 4;
            $y = $baseline - $fraction * $plot['h'];
            $canvas->line($plot['x'], $y, $plot['x'] + $plot['w'], $y, '#e5e5e5');
            $canvas->text($plot['x'] - 6.0, $y + 4.0, SvgCanvas::num($yScale->max * $fraction), [
                'font-size' => '11', 'fill' => '#666', 'text-anchor' => 'end',
            ]);

            $x = $plot['x'] + $fraction * $plot['w'];
            $canvas->text($x, $baseline + 16.0, SvgCanvas::num($xScale->max * $fraction), [
                'font-size' => '11', 'fill' => '#666', 'text-anchor' => 'middle',
            ]);
        }

        $canvas->line($plot['x'], $plot['y'], $plot['x'], $baseline, '#999999');
        $canvas->line($plot['x'], $baseline, $plot['x'] + $plot['w'], $baseline, '#999999');
    }

    private function scatterMaxX(ChartSpec $spec): float
    {
        $max = 0.0;
        foreach ($spec->series as $series) {
            foreach ($series->points as $idx => $point) {
                $max = max($max, $point->x ?? (float) $idx);
            }
        }

        return $max;
    }

    // --- Shared plumbing --------------------------------------------------------

    /**
     * @return array{x: float, y: float, w: float, h: float}
     */
    private function beginPlot(SvgCanvas $canvas, ChartSpec $spec, bool $secondaryAxis = false): array
    {
        $theme = $spec->theme;
        $top = $spec->title !== '' ? 30.0 : 12.0;
        if ($spec->isMultiSeries() && $theme->legend->isVisible()) {
            $top += 18.0;
        }

        $marginLeft = 48.0;
        $marginRight = $secondaryAxis ? 52.0 : 16.0;
        $marginBottom = 42.0;

        return [
            'x' => $marginLeft,
            'y' => $top,
            'w' => max(1.0, $theme->width - $marginLeft - $marginRight),
            'h' => max(1.0, $theme->height - $top - $marginBottom),
        ];
    }

    /**
     * @param array{x: float, y: float, w: float, h: float} $plot
     */
    private function drawAxes(SvgCanvas $canvas, array $plot, LinearScale $scale): void
    {
        $baseline = $plot['y'] + $plot['h'];

        foreach ($scale->ticks(4) as $tick) {
            $ty = $baseline - $scale->lengthOf($tick, $plot['h']);
            $canvas->line($plot['x'], $ty, $plot['x'] + $plot['w'], $ty, '#e5e5e5');
            $canvas->text($plot['x'] - 6.0, $ty + 4.0, SvgCanvas::num($tick), [
                'font-size' => '11',
                'fill' => '#666',
                'text-anchor' => 'end',
            ]);
        }

        $canvas->line($plot['x'], $plot['y'], $plot['x'], $baseline, '#999999');
        $canvas->line($plot['x'], $baseline, $plot['x'] + $plot['w'], $baseline, '#999999');
    }

    /**
     * @param list<string> $categories
     * @param array{x: float, y: float, w: float, h: float} $plot
     */
    private function drawCategoryLabels(SvgCanvas $canvas, array $categories, array $plot, float $groupWidth): void
    {
        $y = $plot['y'] + $plot['h'] + 16.0;
        foreach ($categories as $i => $label) {
            $canvas->text($plot['x'] + $i * $groupWidth + $groupWidth / 2, $y, $label, [
                'font-size' => '11',
                'fill' => '#666',
                'text-anchor' => 'middle',
            ]);
        }
    }

    /**
     * @param array{x: float, y: float, w: float, h: float} $plot
     */
    private function maybeDrawSeriesLegend(SvgCanvas $canvas, ChartSpec $spec, array $plot): void
    {
        if (! $spec->isMultiSeries() || ! $spec->theme->legend->isVisible()) {
            return;
        }

        $cursor = $plot['x'];
        $y = $plot['y'] - 16.0;
        foreach ($spec->series as $si => $series) {
            $color = $series->color ?? $spec->theme->colorAt($si);
            $canvas->rect($cursor, $y, 12.0, 12.0, $color);
            $canvas->text($cursor + 16.0, $y + 10.0, $series->name, ['font-size' => '12', 'fill' => '#333']);
            $cursor += 28.0 + strlen($series->name) * 7.0 + 12.0;
        }
    }

    /**
     * @param list<array{label: string, color: string}> $items
     */
    private function drawLegend(SvgCanvas $canvas, array $items, float $x, float $y): void
    {
        foreach ($items as $i => $item) {
            $iy = $y + $i * 20.0;
            $canvas->rect($x, $iy, 12.0, 12.0, $item['color']);
            $canvas->text($x + 18.0, $iy + 10.0, $item['label'], ['font-size' => '12', 'fill' => '#333']);
        }
    }

}
