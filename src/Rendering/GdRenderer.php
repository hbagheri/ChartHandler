<?php

namespace HBVSoft\ChartHandler\Rendering;

use GdImage;
use HBVSoft\ChartHandler\Exception\MissingExtensionException;
use HBVSoft\ChartHandler\Output\Format;
use HBVSoft\ChartHandler\Output\RenderedChart;
use HBVSoft\ChartHandler\Rendering\Svg\LinearScale;
use HBVSoft\ChartHandler\Spec\Axis;
use HBVSoft\ChartHandler\Spec\ChartSpec;
use HBVSoft\ChartHandler\Spec\ChartType;
use HBVSoft\ChartHandler\Spec\SeriesType;
use RuntimeException;

/**
 * Raster backend built on PHP's standard `gd` extension — no external chart library.
 * Emits PNG/JPEG/GIF/WebP, which is what makes charts render in HTML email clients that
 * don't support inline SVG (e.g. Outlook desktop). Covers the core 5 chart types and
 * mirrors SvgRenderer's layout so the two backends produce comparable charts.
 *
 * Text uses GD's built-in bitmap fonts (no font file required); labels are best with
 * Latin characters.
 */
final class GdRenderer extends AbstractRenderer
{
    public function __construct()
    {
        if (! extension_loaded('gd')) {
            throw MissingExtensionException::forRenderer('gd', self::class);
        }
    }

    public function supportedFormats(): array
    {
        return [Format::Png, Format::Jpeg, Format::Gif, Format::Webp];
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
        ];
    }

    protected function doRender(ChartSpec $spec, Format $format): RenderedChart
    {
        $theme = $spec->theme;
        $img = imagecreatetruecolor($theme->width, $theme->height)
            ?: throw new RuntimeException('GD could not allocate the image canvas.');

        $this->fillBackground($img, $theme->background, $format, $theme->width, $theme->height);

        match ($spec->type) {
            ChartType::Pie => $this->drawPie($img, $spec, false),
            ChartType::Donut => $this->drawPie($img, $spec, true),
            ChartType::Bar => $this->drawBars($img, $spec),
            ChartType::StackedBar => $this->drawStackedBar($img, $spec),
            ChartType::Line => $this->drawLines($img, $spec, false),
            ChartType::Area => $this->drawLines($img, $spec, true),
            ChartType::Combo => $this->drawCombo($img, $spec),
            default => null, // unreachable: guarded by AbstractRenderer::render()
        };

        if ($spec->title !== '') {
            $this->text($img, 5, $theme->width / 2, 6, $spec->title, $this->color($img, '#333333'), 'center');
        }

        $bytes = $this->encode($img, $format);
        imagedestroy($img);

        return new RenderedChart($bytes, $format);
    }

    private function fillBackground(GdImage $img, ?string $background, Format $format, int $w, int $h): void
    {
        imagealphablending($img, false);

        if ($background === null && $format !== Format::Jpeg) {
            $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
            imagefilledrectangle($img, 0, 0, $w, $h, $transparent === false ? 0 : $transparent);
            imagesavealpha($img, true);
        } else {
            imagefilledrectangle($img, 0, 0, $w, $h, $this->color($img, $background ?? '#ffffff'));
        }

        imagealphablending($img, true);
    }

    // --- Non-axis: pie & donut --------------------------------------------------

    private function drawPie(GdImage $img, ChartSpec $spec, bool $donut): void
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
        $diameter = 2.0 * $radius;

        /** @var list<array{label: string, color: string}> $legend */
        $legend = [];
        $cursor = 270.0; // start at 12 o'clock; GD angles increase clockwise
        foreach ($points as $i => $point) {
            $hex = $point->color ?? $theme->colorAt($i);
            $sweep = (abs($point->value) / $total) * 360.0;
            $start = fmod($cursor, 360.0);

            if ($sweep >= 359.999) {
                imagefilledellipse($img, (int) round($cx), (int) round($cy), (int) round($diameter), (int) round($diameter), $this->color($img, $hex));
            } else {
                imagefilledarc($img, (int) round($cx), (int) round($cy), (int) round($diameter), (int) round($diameter), (int) round($start), (int) round($start + $sweep), $this->color($img, $hex), IMG_ARC_PIE);
            }
            $cursor += $sweep;
            $legend[] = ['label' => $point->label ?? 'Item ' . ($i + 1), 'color' => $hex];
        }

        if ($donut) {
            $this->punchHole($img, $cx, $cy, $diameter * 0.55, $theme->background);
        }

        if ($legendVisible) {
            $this->drawLegend($img, $legend, (int) round($theme->width - $legendWidth + 12.0), (int) round($top + 12.0));
        }
    }

    private function punchHole(GdImage $img, float $cx, float $cy, float $diameter, ?string $background): void
    {
        $d = (int) round($diameter);
        $x = (int) round($cx);
        $y = (int) round($cy);

        if ($background === null) {
            imagealphablending($img, false);
            $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
            imagefilledellipse($img, $x, $y, $d, $d, $transparent === false ? 0 : $transparent);
            imagealphablending($img, true);

            return;
        }

        imagefilledellipse($img, $x, $y, $d, $d, $this->color($img, $background));
    }

    // --- Axis: bars -------------------------------------------------------------

    private function drawBars(GdImage $img, ChartSpec $spec): void
    {
        $plot = $this->beginPlot($spec);
        $categories = PlotData::categories($spec);
        $count = count($categories);
        if ($count === 0) {
            return;
        }

        $scale = new LinearScale(PlotData::maxValue($spec));
        $this->drawAxes($img, $plot, $scale);

        $seriesCount = max(1, count($spec->series));
        $groupWidth = $plot['w'] / $count;
        $bandWidth = $groupWidth * 0.8;
        $barWidth = $bandWidth / $seriesCount;
        $baseline = $plot['y'] + $plot['h'];

        foreach ($spec->series as $si => $series) {
            $color = $this->color($img, $series->color ?? $spec->theme->colorAt($si));
            foreach ($series->points as $ci => $point) {
                if ($ci >= $count) {
                    break;
                }
                $height = $scale->lengthOf(max(0.0, $point->value), $plot['h']);
                $bx = $plot['x'] + $ci * $groupWidth + ($groupWidth - $bandWidth) / 2 + $si * $barWidth;
                $top = (int) round($baseline - $height);
                $bottom = (int) round($baseline);
                if ($bottom > $top) {
                    imagefilledrectangle($img, (int) round($bx), $top, (int) round($bx + max(1.0, $barWidth - 1.0)), $bottom, $color);
                }
            }
        }

        $this->drawCategoryLabels($img, $categories, $plot, $groupWidth);
        $this->maybeSeriesLegend($img, $spec, $plot);
    }

    // --- Axis: lines & areas ----------------------------------------------------

    private function drawLines(GdImage $img, ChartSpec $spec, bool $area): void
    {
        $plot = $this->beginPlot($spec);
        $categories = PlotData::categories($spec);
        $count = count($categories);
        if ($count === 0) {
            return;
        }

        $scale = new LinearScale(PlotData::maxValue($spec));
        $this->drawAxes($img, $plot, $scale);

        $groupWidth = $plot['w'] / $count;
        $baseline = $plot['y'] + $plot['h'];

        imagesetthickness($img, 2);
        foreach ($spec->series as $si => $series) {
            $hex = $series->color ?? $spec->theme->colorAt($si);
            $color = $this->color($img, $hex);

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

            if ($area) {
                $polygon = [(int) round($pts[0]['x']), (int) round($baseline)];
                foreach ($pts as $p) {
                    $polygon[] = (int) round($p['x']);
                    $polygon[] = (int) round($p['y']);
                }
                $polygon[] = (int) round($pts[count($pts) - 1]['x']);
                $polygon[] = (int) round($baseline);
                imagefilledpolygon($img, $polygon, $this->colorAlpha($img, $hex, 95));
            }

            for ($i = 1, $n = count($pts); $i < $n; $i++) {
                imageline($img, (int) round($pts[$i - 1]['x']), (int) round($pts[$i - 1]['y']), (int) round($pts[$i]['x']), (int) round($pts[$i]['y']), $color);
            }
            foreach ($pts as $p) {
                imagefilledellipse($img, (int) round($p['x']), (int) round($p['y']), 6, 6, $color);
            }
        }
        imagesetthickness($img, 1);

        $this->drawCategoryLabels($img, $categories, $plot, $groupWidth);
        $this->maybeSeriesLegend($img, $spec, $plot);
    }

    // --- Axis: stacked bars -----------------------------------------------------

    private function drawStackedBar(GdImage $img, ChartSpec $spec): void
    {
        $categories = PlotData::categories($spec);
        $count = count($categories);
        if ($count === 0) {
            return;
        }

        $scale = new LinearScale($this->stackedMax($spec, $count));
        $plot = $this->beginPlot($spec);
        $this->drawAxes($img, $plot, $scale);

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
                $yBottom = (int) round($baseline - $scale->lengthOf($cumulative, $plot['h']));
                $yTop = (int) round($baseline - $scale->lengthOf($cumulative + $value, $plot['h']));
                if ($yBottom > $yTop) {
                    imagefilledrectangle($img, (int) round($bx), $yTop, (int) round($bx + $barWidth), $yBottom, $this->color($img, $series->color ?? $spec->theme->colorAt($si)));
                }
                $cumulative += $value;
            }
        }

        $this->drawCategoryLabels($img, $categories, $plot, $groupWidth);
        $this->maybeSeriesLegend($img, $spec, $plot);
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

    private function drawCombo(GdImage $img, ChartSpec $spec): void
    {
        $categories = PlotData::categories($spec);
        $count = count($categories);
        if ($count === 0) {
            return;
        }

        [$leftScale, $rightScale] = $this->comboScales($spec);
        $plot = $this->beginPlot($spec, $rightScale !== null);
        $this->drawComboAxes($img, $plot, $leftScale, $rightScale);

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
            $color = $this->color($img, $hex);

            if ($type === SeriesType::Bar) {
                foreach ($series->points as $ci => $point) {
                    if ($ci >= $count) {
                        break;
                    }
                    $height = $scale->lengthOf(max(0.0, $point->value), $plot['h']);
                    $bx = $plot['x'] + $ci * $groupWidth + ($groupWidth - $bandWidth) / 2 + $barSlot * $barWidth;
                    $top = (int) round($baseline - $height);
                    $bottom = (int) round($baseline);
                    if ($bottom > $top) {
                        imagefilledrectangle($img, (int) round($bx), $top, (int) round($bx + max(1.0, $barWidth - 1.0)), $bottom, $color);
                    }
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

            if ($type === SeriesType::Area) {
                $polygon = [(int) round($pts[0]['x']), (int) round($baseline)];
                foreach ($pts as $p) {
                    $polygon[] = (int) round($p['x']);
                    $polygon[] = (int) round($p['y']);
                }
                $polygon[] = (int) round($pts[count($pts) - 1]['x']);
                $polygon[] = (int) round($baseline);
                imagefilledpolygon($img, $polygon, $this->colorAlpha($img, $hex, 95));
            }

            imagesetthickness($img, 2);
            for ($i = 1, $n = count($pts); $i < $n; $i++) {
                imageline($img, (int) round($pts[$i - 1]['x']), (int) round($pts[$i - 1]['y']), (int) round($pts[$i]['x']), (int) round($pts[$i]['y']), $color);
            }
            imagesetthickness($img, 1);
            foreach ($pts as $p) {
                imagefilledellipse($img, (int) round($p['x']), (int) round($p['y']), 6, 6, $color);
            }
        }

        $this->drawCategoryLabels($img, $categories, $plot, $groupWidth);
        $this->maybeSeriesLegend($img, $spec, $plot);
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
    private function drawComboAxes(GdImage $img, array $plot, LinearScale $left, ?LinearScale $right): void
    {
        $baseline = $plot['y'] + $plot['h'];
        $grid = $this->color($img, '#e5e5e5');
        $axis = $this->color($img, '#999999');
        $label = $this->color($img, '#666666');

        for ($i = 0; $i <= 4; $i++) {
            $fraction = $i / 4;
            $y = $baseline - $fraction * $plot['h'];
            imageline($img, (int) round($plot['x']), (int) round($y), (int) round($plot['x'] + $plot['w']), (int) round($y), $grid);
            $this->text($img, 2, $plot['x'] - 6.0, $y, $this->num($left->max * $fraction), $label, 'right', 'middle');
            if ($right !== null) {
                $this->text($img, 2, $plot['x'] + $plot['w'] + 6.0, $y, $this->num($right->max * $fraction), $label, 'left', 'middle');
            }
        }

        imageline($img, (int) round($plot['x']), (int) round($plot['y']), (int) round($plot['x']), (int) round($baseline), $axis);
        imageline($img, (int) round($plot['x']), (int) round($baseline), (int) round($plot['x'] + $plot['w']), (int) round($baseline), $axis);
        if ($right !== null) {
            imageline($img, (int) round($plot['x'] + $plot['w']), (int) round($plot['y']), (int) round($plot['x'] + $plot['w']), (int) round($baseline), $axis);
        }
    }

    // --- Shared plumbing --------------------------------------------------------

    /**
     * @return array{x: float, y: float, w: float, h: float}
     */
    private function beginPlot(ChartSpec $spec, bool $secondaryAxis = false): array
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
    private function drawAxes(GdImage $img, array $plot, LinearScale $scale): void
    {
        $baseline = $plot['y'] + $plot['h'];
        $grid = $this->color($img, '#e5e5e5');
        $axis = $this->color($img, '#999999');
        $label = $this->color($img, '#666666');

        foreach ($scale->ticks(4) as $tick) {
            $ty = $baseline - $scale->lengthOf($tick, $plot['h']);
            imageline($img, (int) round($plot['x']), (int) round($ty), (int) round($plot['x'] + $plot['w']), (int) round($ty), $grid);
            $this->text($img, 2, $plot['x'] - 6.0, $ty, $this->num($tick), $label, 'right', 'middle');
        }

        imageline($img, (int) round($plot['x']), (int) round($plot['y']), (int) round($plot['x']), (int) round($baseline), $axis);
        imageline($img, (int) round($plot['x']), (int) round($baseline), (int) round($plot['x'] + $plot['w']), (int) round($baseline), $axis);
    }

    /**
     * @param list<string> $categories
     * @param array{x: float, y: float, w: float, h: float} $plot
     */
    private function drawCategoryLabels(GdImage $img, array $categories, array $plot, float $groupWidth): void
    {
        $label = $this->color($img, '#666666');
        $y = $plot['y'] + $plot['h'] + 6.0;
        foreach ($categories as $i => $text) {
            $this->text($img, 2, $plot['x'] + $i * $groupWidth + $groupWidth / 2, $y, $text, $label, 'center');
        }
    }

    /**
     * @param array{x: float, y: float, w: float, h: float} $plot
     */
    private function maybeSeriesLegend(GdImage $img, ChartSpec $spec, array $plot): void
    {
        if (! $spec->isMultiSeries() || ! $spec->theme->legend->isVisible()) {
            return;
        }

        $dark = $this->color($img, '#333333');
        $cursor = $plot['x'];
        $y = $plot['y'] - 16.0;
        foreach ($spec->series as $si => $series) {
            $swatch = $this->color($img, $series->color ?? $spec->theme->colorAt($si));
            imagefilledrectangle($img, (int) round($cursor), (int) round($y), (int) round($cursor + 12.0), (int) round($y + 12.0), $swatch);
            $this->text($img, 2, $cursor + 16.0, $y, $series->name, $dark);
            $cursor += 28.0 + strlen($series->name) * imagefontwidth(2) + 12.0;
        }
    }

    /**
     * @param list<array{label: string, color: string}> $items
     */
    private function drawLegend(GdImage $img, array $items, int $x, int $y): void
    {
        $dark = $this->color($img, '#333333');
        foreach ($items as $i => $item) {
            $iy = $y + $i * 20;
            imagefilledrectangle($img, $x, $iy, $x + 12, $iy + 12, $this->color($img, $item['color']));
            $this->text($img, 2, (float) ($x + 18), (float) $iy, $item['label'], $dark);
        }
    }

    private function text(GdImage $img, int $font, float $x, float $y, string $text, int $color, string $halign = 'left', string $valign = 'top'): void
    {
        $width = imagefontwidth($font) * strlen($text);
        $height = imagefontheight($font);

        $px = match ($halign) {
            'center' => $x - $width / 2,
            'right' => $x - $width,
            default => $x,
        };
        $py = match ($valign) {
            'middle' => $y - $height / 2,
            'bottom' => $y - $height,
            default => $y,
        };

        imagestring($img, $font, (int) round($px), (int) round($py), $text, $color);
    }

    private function encode(GdImage $img, Format $format): string
    {
        ob_start();
        match ($format) {
            Format::Png => imagepng($img),
            Format::Jpeg => imagejpeg($img, null, 90),
            Format::Gif => imagegif($img),
            Format::Webp => imagewebp($img),
            default => false, // unreachable: guarded by supportedFormats()
        };
        $bytes = ob_get_clean();

        return $bytes === false ? '' : $bytes;
    }

    private function color(GdImage $img, string $hex): int
    {
        [$r, $g, $b] = $this->hexToRgb($hex);
        $color = imagecolorallocate($img, $r, $g, $b);

        return $color === false ? 0 : $color;
    }

    private function colorAlpha(GdImage $img, string $hex, int $alpha): int
    {
        [$r, $g, $b] = $this->hexToRgb($hex);
        $color = imagecolorallocatealpha($img, $r, $g, $b, $alpha);

        return $color === false ? 0 : $color;
    }

    /**
     * @return array{int, int, int}
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) < 6 || ! ctype_xdigit(substr($hex, 0, 6))) {
            return [0, 0, 0];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    private function num(float $value): string
    {
        $formatted = number_format($value, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }
}
