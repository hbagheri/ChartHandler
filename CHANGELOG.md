# Changelog

All notable changes to this project are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2026-05-20

### Added
- **Combo charts with a secondary (right) Y-axis.** Mix bar/line/area series in a single
  chart, each bound to the left or right axis with independent auto-scaling:
  - new `Spec\Axis` (Left/Right) and `Spec\SeriesType` (Bar/Line/Area) enums,
  - `ChartType::Combo`, and `Series::withType()` / `withAxis()`,
  - facade `Chart::combo()->addBar()/addLine()/addArea($name, $data, Axis::Right)`.
  Rendered by both the SVG and GD backends (right axis scaled independently — no manual
  value scaling needed).

## [1.1.0] - 2026-05-20

First functional release. (The earlier `1.0.0` tag pointed at an initial non-working
skeleton and predates this rewrite.)

### Added
- Fluent `Chart` facade: `Chart::pie/donut/bar/line/area(...)` with
  `title/categories/size/legend/palette/background/addSeries` and outputs
  `toSvg/toPng/toJpeg/toDataUri/toHtmlImg/toEmailImg/save/render`.
- `SvgRenderer` (pure PHP) — pie, donut, bar, line, area.
- `GdRenderer` (PNG/JPEG/GIF/WebP via `ext-gd`) — pie, donut, bar, line, area.
- `RendererRegistry` resolving an output `Format` to a backend (GD created lazily).
- Backend-agnostic spec model: `ChartSpec`, `ChartType`, `Series`, `DataPoint`, `Theme`,
  `LegendPosition`.
- `RenderedChart` output value object: `toBinary/toBase64/toDataUri/toHtmlImg/save`.
- `Format` enum (Png/Jpeg/Gif/Webp/Svg) with mime/extension helpers.
- Typed exceptions under the `ChartHandlerException` marker interface.
- README, examples, and CI (PHPStan level 6 + PHPUnit on PHP 8.1–8.3).

[Unreleased]: https://github.com/hbagheri/ChartHandler/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/hbagheri/ChartHandler/releases/tag/v1.2.0
[1.1.0]: https://github.com/hbagheri/ChartHandler/releases/tag/v1.1.0
