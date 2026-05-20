<?php

namespace HBVSoft\ChartHandler;

use HBVSoft\ChartHandler\Exception\InvalidChartDataException;
use HBVSoft\ChartHandler\Output\Format;
use HBVSoft\ChartHandler\Output\RenderedChart;
use HBVSoft\ChartHandler\Rendering\RendererRegistry;
use HBVSoft\ChartHandler\Spec\Axis;
use HBVSoft\ChartHandler\Spec\ChartSpec;
use HBVSoft\ChartHandler\Spec\ChartType;
use HBVSoft\ChartHandler\Spec\LegendPosition;
use HBVSoft\ChartHandler\Spec\Series;
use HBVSoft\ChartHandler\Spec\SeriesType;
use HBVSoft\ChartHandler\Spec\Theme;
use InvalidArgumentException;

/**
 * Fluent entry point. Build a chart from plain data and output it in any format:
 *
 *   Chart::pie(['Chrome' => 63, 'Firefox' => 19])->title('Share')->toEmailImg();
 *   Chart::bar([12, 19, 7])->categories(['Jan', 'Feb', 'Mar'])->toPng();
 *   Chart::line([Series::fromValues('2024', [1, 2, 3])])->toSvg();
 *
 * Data may be a values array (`[label => value]` or `[v1, v2, …]`), a single Series,
 * or a list of Series for multi-series charts.
 */
final class Chart
{
    private string $title = '';

    /** @var list<Series> */
    private array $series;

    /** @var list<string> */
    private array $categories = [];

    private Theme $theme;

    private RendererRegistry $registry;

    /**
     * @param list<Series> $series
     */
    public function __construct(private readonly ChartType $type, array $series, ?RendererRegistry $registry = null)
    {
        $this->series = $series;
        $this->theme = new Theme();
        $this->registry = $registry ?? new RendererRegistry();
    }

    // --- Factories --------------------------------------------------------------

    /**
     * @param Series|array<array-key, int|float|Series> $data
     */
    public static function pie(Series|array $data): self
    {
        return new self(ChartType::Pie, self::normalize($data, 'Data'));
    }

    /**
     * @param Series|array<array-key, int|float|Series> $data
     */
    public static function donut(Series|array $data): self
    {
        return new self(ChartType::Donut, self::normalize($data, 'Data'));
    }

    /**
     * @param Series|array<array-key, int|float|Series> $data
     */
    public static function bar(Series|array $data): self
    {
        return new self(ChartType::Bar, self::normalize($data, 'Series'));
    }

    /**
     * @param Series|array<array-key, int|float|Series> $data
     */
    public static function line(Series|array $data): self
    {
        return new self(ChartType::Line, self::normalize($data, 'Series'));
    }

    /**
     * @param Series|array<array-key, int|float|Series> $data
     */
    public static function area(Series|array $data): self
    {
        return new self(ChartType::Area, self::normalize($data, 'Series'));
    }

    /**
     * A combo chart: add bar/line/area series with per-series axis assignment.
     *
     *   Chart::combo()
     *       ->addBar('Revenue', [120, 190, 70, 220])
     *       ->addLine('Conversion %', [3.2, 4.1, 2.8, 5.0], Axis::Right)
     *       ->categories(['Q1', 'Q2', 'Q3', 'Q4']);
     */
    public static function combo(): self
    {
        return new self(ChartType::Combo, []);
    }

    /**
     * A stacked bar chart: series are stacked on top of each other per category.
     * Pass a list of Series (or add them with addSeries()).
     *
     * @param Series|array<array-key, int|float|Series> $data
     */
    public static function stackedBar(Series|array $data): self
    {
        return new self(ChartType::StackedBar, self::normalize($data, 'Series'));
    }

    // --- Fluent configuration ---------------------------------------------------

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param list<string> $categories
     */
    public function categories(array $categories): self
    {
        $this->categories = array_values($categories);

        return $this;
    }

    /**
     * @param Series|array<array-key, int|float|Series> $data
     */
    public function addSeries(Series|array $data, string $name = 'Series'): self
    {
        $this->series = [...$this->series, ...self::normalize($data, $name)];

        return $this;
    }

    /**
     * Add a bar series to a combo chart, bound to the given axis.
     *
     * @param Series|array<array-key, int|float> $data
     */
    public function addBar(string $name, Series|array $data, Axis $axis = Axis::Left): self
    {
        $this->series[] = $this->comboSeries($name, $data, SeriesType::Bar, $axis);

        return $this;
    }

    /**
     * Add a line series to a combo chart, bound to the given axis.
     *
     * @param Series|array<array-key, int|float> $data
     */
    public function addLine(string $name, Series|array $data, Axis $axis = Axis::Left): self
    {
        $this->series[] = $this->comboSeries($name, $data, SeriesType::Line, $axis);

        return $this;
    }

    /**
     * Add an area series to a combo chart, bound to the given axis.
     *
     * @param Series|array<array-key, int|float> $data
     */
    public function addArea(string $name, Series|array $data, Axis $axis = Axis::Left): self
    {
        $this->series[] = $this->comboSeries($name, $data, SeriesType::Area, $axis);

        return $this;
    }

    /**
     * @param Series|array<array-key, int|float> $data
     */
    private function comboSeries(string $name, Series|array $data, SeriesType $type, Axis $axis): Series
    {
        $series = $data instanceof Series ? $data : Series::fromValues($name, $data);

        return $series->withType($type)->withAxis($axis);
    }

    public function size(int $width, int $height): self
    {
        $this->theme = $this->theme->withSize($width, $height);

        return $this;
    }

    public function legend(LegendPosition $position): self
    {
        $this->theme = $this->theme->withLegend($position);

        return $this;
    }

    /**
     * @param list<string> $palette
     */
    public function palette(array $palette): self
    {
        $this->theme = $this->theme->withPalette($palette);

        return $this;
    }

    public function background(?string $color): self
    {
        $this->theme = $this->theme->withBackground($color);

        return $this;
    }

    public function theme(Theme $theme): self
    {
        $this->theme = $theme;

        return $this;
    }

    public function renderers(RendererRegistry $registry): self
    {
        $this->registry = $registry;

        return $this;
    }

    // --- Output -----------------------------------------------------------------

    public function toSpec(): ChartSpec
    {
        return new ChartSpec($this->type, $this->series, $this->title, $this->categories, $this->theme);
    }

    public function render(Format $format): RenderedChart
    {
        return $this->registry->rendererFor($format)->render($this->toSpec(), $format);
    }

    public function toSvg(): string
    {
        return $this->render(Format::Svg)->toBinary();
    }

    public function toPng(): string
    {
        return $this->render(Format::Png)->toBinary();
    }

    public function toJpeg(): string
    {
        return $this->render(Format::Jpeg)->toBinary();
    }

    public function toDataUri(Format $format = Format::Png): string
    {
        return $this->render($format)->toDataUri();
    }

    /**
     * @param array<string, string|int> $attributes
     */
    public function toHtmlImg(Format $format = Format::Png, array $attributes = []): string
    {
        return $this->render($format)->toHtmlImg($attributes);
    }

    /**
     * Self-contained PNG <img> for HTML email (PNG renders in all clients, unlike SVG).
     *
     * @param array<string, string|int> $attributes
     */
    public function toEmailImg(array $attributes = []): string
    {
        return $this->render(Format::Png)->toHtmlImg($attributes);
    }

    /**
     * Save to a file; the format is inferred from the path extension.
     */
    public function save(string $path): bool
    {
        $format = Format::fromExtension(pathinfo($path, PATHINFO_EXTENSION));

        return $this->render($format)->save($path);
    }

    // --- Internals --------------------------------------------------------------

    /**
     * @param Series|array<array-key, int|float|Series> $data
     *
     * @return list<Series>
     */
    private static function normalize(Series|array $data, string $name): array
    {
        if ($data instanceof Series) {
            return [$data];
        }

        if ($data === []) {
            throw InvalidChartDataException::emptySeries();
        }

        if (reset($data) instanceof Series) {
            $series = [];
            foreach ($data as $entry) {
                if (! $entry instanceof Series) {
                    throw new InvalidArgumentException('Cannot mix Series objects with plain values.');
                }
                $series[] = $entry;
            }

            return $series;
        }

        $values = [];
        foreach ($data as $key => $value) {
            if ($value instanceof Series) {
                throw new InvalidArgumentException('Cannot mix Series objects with plain values.');
            }
            $values[$key] = $value;
        }

        return [Series::fromValues($name, $values)];
    }
}
