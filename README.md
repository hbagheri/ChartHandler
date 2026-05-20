# ChartHandler

[![CI](https://github.com/hbagheri/ChartHandler/actions/workflows/ci.yml/badge.svg)](https://github.com/hbagheri/ChartHandler/actions/workflows/ci.yml)

A clean, dependency-light PHP charting library with **two rendering backends** (pure-PHP
SVG and GD raster) and multiple output formats — designed so charts drop straight into
**HTML emails as inline base64 images**, even offline.

```php
use HBVSoft\ChartHandler\Chart;

// A PNG <img> tag you can paste into an HTML email — no external requests.
echo Chart::pie(['Chrome' => 63, 'Firefox' => 19, 'Safari' => 18])
    ->title('Browser share')
    ->toEmailImg();
```

## Features

- **Chart types:** pie, donut, bar, line, area, and **combo** (mixed bars + line/area with a secondary axis).
- **Two backends, zero external chart libraries:**
  - `SvgRenderer` — pure PHP, crisp and scalable (great for the web).
  - `GdRenderer` — PNG / JPEG / GIF / WebP via PHP's standard `gd` extension.
- **Output anywhere:** raw bytes, file, base64, `data:` URI, or a ready-to-embed `<img>` tag.
- **Email-first:** `toEmailImg()` produces a self-contained PNG `<img>` that renders in
  every client (including Outlook desktop, which does **not** render inline SVG).
- Fluent API; strict types; PHPStan level 6; tested.

## Requirements

- PHP **8.1+**
- `ext-gd` — only for raster output (PNG/JPEG/GIF/WebP). SVG output needs no extensions.

## Installation

```bash
composer require hbvsoft/charthandler
```

## Quickstart

```php
use HBVSoft\ChartHandler\Chart;
use HBVSoft\ChartHandler\Spec\Series;

// Pie from an associative array (keys become slice labels)
Chart::pie(['Chrome' => 63, 'Firefox' => 19, 'Safari' => 18])
    ->title('Browser share')
    ->save('browser-share.png');          // format inferred from the extension

// Bar with explicit categories
Chart::bar([12, 19, 7, 22, 15])
    ->title('Monthly revenue')
    ->categories(['Jan', 'Feb', 'Mar', 'Apr', 'May'])
    ->save('revenue.svg');

// Multi-series line
Chart::line([Series::fromValues('2024', [10, 14, 9, 18])])
    ->addSeries(Series::fromValues('2025', [13, 11, 17, 21]))
    ->categories(['Q1', 'Q2', 'Q3', 'Q4'])
    ->toPng();                            // raw PNG bytes

// Donut as an SVG string
$svg = Chart::donut(['Linux' => 45, 'Windows' => 35, 'macOS' => 20])->toSvg();
```

## Combo charts (dual axis)

Mix bars with a line/area in one chart, each bound to its own axis. The secondary (right)
axis is scaled independently, so you never pre-scale the line's values:

```php
use HBVSoft\ChartHandler\Chart;
use HBVSoft\ChartHandler\Spec\Axis;

Chart::combo()
    ->addBar('Revenue', [120, 190, 70, 220])
    ->addLine('Conversion %', [3.2, 4.1, 2.8, 5.0], Axis::Right)  // right axis, own scale
    ->title('Revenue vs conversion')
    ->categories(['Q1', 'Q2', 'Q3', 'Q4'])
    ->toEmailImg();
```

`addBar()`, `addLine()`, and `addArea()` each take
`(string $name, array|Series $data, Axis $axis = Axis::Left)`.

## Output methods

| Method | Returns | Notes |
|---|---|---|
| `toSvg()` | `string` | SVG markup |
| `toPng()` / `toJpeg()` | `string` | raw image bytes (needs `ext-gd`) |
| `toDataUri(Format = Png)` | `string` | `data:<mime>;base64,…` |
| `toHtmlImg(Format = Png, $attrs = [])` | `string` | `<img src="data:…" …>` |
| `toEmailImg($attrs = [])` | `string` | PNG `<img>` — **use this for email** |
| `save($path)` | `bool` | format inferred from the file extension |
| `render(Format)` | `RenderedChart` | the value object below, for full control |

`Format` (`HBVSoft\ChartHandler\Output\Format`): `Png`, `Jpeg`, `Gif`, `Webp`, `Svg`.

## Charts in HTML email (the main use case)

Email clients can't fetch external images when offline, and **many (Outlook desktop,
some Gmail setups) won't render inline SVG at all**. The reliable approach is a base64
**PNG** embedded directly in the markup:

```php
$img = Chart::bar(['Mon' => 8, 'Tue' => 12, 'Wed' => 5, 'Thu' => 14, 'Fri' => 9])
    ->title('This week')
    ->toEmailImg(['alt' => 'Weekly activity', 'width' => 480]);

$html = "<h1>Your weekly report</h1>{$img}";
// → <img src="data:image/png;base64,iVBORw0KGgo..." alt="Weekly activity" width="480" />
```

No `<img src="https://…">`, so nothing to load — it just shows up.

## Styling

```php
use HBVSoft\ChartHandler\Spec\LegendPosition;

Chart::pie($data)
    ->size(500, 500)
    ->legend(LegendPosition::Bottom)        // None | Top | Right | Bottom | Left
    ->palette(['#4e79a7', '#f28e2b', '#e15759'])
    ->background('#ffffff')                 // or null for transparent (PNG/SVG)
    ->toPng();
```

Per-slice / per-series colors and labels are available via the lower-level `Series` /
`DataPoint` value objects in `HBVSoft\ChartHandler\Spec`.

## A complete example

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use HBVSoft\ChartHandler\Chart;
use HBVSoft\ChartHandler\Spec\Axis;
use HBVSoft\ChartHandler\Spec\Series;

// 1) A pie, saved straight to PNG (format inferred from the extension)
Chart::pie(['Chrome' => 63, 'Firefox' => 19, 'Safari' => 18])
    ->title('Browser share')
    ->save('browser-share.png');

// 2) A combo chart: bars + a line on an independently-scaled right axis, as SVG
Chart::combo()
    ->addBar('Revenue', [120, 190, 70, 220])
    ->addLine('Conversion %', [3.2, 4.1, 2.8, 5.0], Axis::Right)
    ->title('Revenue vs conversion')
    ->categories(['Q1', 'Q2', 'Q3', 'Q4'])
    ->save('combo.svg');

// 3) A multi-series line as a self-contained PNG <img> for an HTML email
$img = Chart::line([Series::fromValues('2024', [10, 14, 9, 18])])
    ->addSeries(Series::fromValues('2025', [13, 11, 17, 21]))
    ->categories(['Q1', 'Q2', 'Q3', 'Q4'])
    ->title('Signups')
    ->toEmailImg(['alt' => 'Signups', 'width' => 480]);

file_put_contents('report.html', "<h1>Monthly report</h1>{$img}");

echo "Wrote browser-share.png, combo.svg and report.html\n";
```

## Using it in Laravel

**Serve a chart as an image response:**

```php
use HBVSoft\ChartHandler\Chart;
use Illuminate\Support\Facades\Route;

Route::get('/charts/weekly.png', function () {
    $png = Chart::bar(['Mon' => 8, 'Tue' => 12, 'Wed' => 5, 'Thu' => 14, 'Fri' => 9])
        ->title('This week')
        ->toPng();

    return response($png)->header('Content-Type', 'image/png');
});
```

**Embed one inline in a Blade view** (no external request — great for PDFs/print):

```php
// in the controller
$chart = Chart::pie(['A' => 40, 'B' => 35, 'C' => 25])->title('Split');
return view('dashboard', ['chartUri' => $chart->toDataUri()]);
```

```blade
{{-- resources/views/dashboard.blade.php --}}
<img src="{{ $chartUri }}" alt="Split">
```

**Inline charts in a Mailable** (renders offline, including in Outlook):

```php
use HBVSoft\ChartHandler\Chart;
use HBVSoft\ChartHandler\Spec\Series;

$img = Chart::line([Series::fromValues('Signups', [10, 18, 24, 30])])
    ->categories(['Jan', 'Feb', 'Mar', 'Apr'])
    ->toEmailImg(['alt' => 'Signups']);

Mail::html("<h1>Monthly report</h1>{$img}", function ($m) {
    $m->to('user@example.com')->subject('Your report');
});
```

## Lower-level API

The facade is sugar over a small pipeline you can use directly:

```php
use HBVSoft\ChartHandler\Spec\{ChartSpec, ChartType, Series};
use HBVSoft\ChartHandler\Rendering\SvgRenderer;
use HBVSoft\ChartHandler\Output\Format;

$spec = new ChartSpec(ChartType::Pie, [Series::fromValues('S', ['a' => 1, 'b' => 2])]);
$rendered = (new SvgRenderer())->render($spec, Format::Svg);

$rendered->toBinary();   // bytes
$rendered->toBase64();   // base64 string
$rendered->toDataUri();  // data: URI
$rendered->toHtmlImg();  // <img …>
$rendered->save('chart.svg');
```

- **Chart-type builders** produce a backend-agnostic `ChartSpec`.
- **Renderers** (`SvgRenderer`, `GdRenderer`) turn a `ChartSpec` into a `RenderedChart`.
- A `RendererRegistry` picks the backend for a requested `Format`.

## Error handling

Every exception implements `HBVSoft\ChartHandler\Exception\ChartHandlerException`, so you
can catch them all at once:

```php
use HBVSoft\ChartHandler\Exception\ChartHandlerException;

try {
    Chart::pie([])->toPng();
} catch (ChartHandlerException $e) {
    // InvalidChartDataException, UnsupportedFormatException,
    // UnsupportedChartTypeException, MissingExtensionException
}
```

## Development

```bash
composer install
composer test     # PHPUnit
composer stan     # PHPStan (level 6)
```

> The test suite needs `ext-dom`/`ext-xml` (PHPUnit) and `ext-gd` (raster tests). If your
> CLI lacks them, run inside a container that has them.

## License

MIT — see [LICENSE](LICENSE).
