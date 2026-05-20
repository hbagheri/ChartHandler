# Changelog

All notable changes to this project are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-05-20

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

[Unreleased]: https://github.com/hbagheri/ChartHandler/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/hbagheri/ChartHandler/releases/tag/v1.0.0
