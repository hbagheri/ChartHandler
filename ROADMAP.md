# ChartHandler — Roadmap

> A clean, extensible, **public Composer package** for generating charts in PHP.
>
> _Revised 2026-05-20: jpgraph dropped in favour of a built-in GD renderer; fluent facade
> added; docs/CI/release pulled earlier; JS-config backend removed._

## Vision

ChartHandler turns data into charts through a fluent, backend-agnostic API:

- **Two rendering backends, no external chart library:**
  - **SVG** — pure PHP, scalable, great for the web.
  - **GD (raster)** — PNG/JPEG/GIF/WebP via PHP's standard `gd` extension.
- **User-selectable output format** — PNG, JPEG, GIF, WebP, SVG.
- **Multiple output targets** — raw binary, save to file, **base64 / data-URI**, and a
  ready-to-embed `<img>` tag.
- **Offline-email use case is first-class** — embed charts as base64 `data:` images so
  they render with no internet access / no external `src` fetch.
  - ⚠️ Because **SVG does not render in several email clients (Outlook desktop, some Gmail)**,
    the email helper defaults to **PNG** (via the GD backend). SVG remains the default for web.
- **Extensible chart-type system** with a documented recipe to add new types.

---

## Target architecture

```
                 ┌────────────────────────────────────────────┐
   Your code ──► │ Fluent facade  Chart::pie($data)->toPng()   │
                 │  ->toEmailImg() ->toDataUri() ->toSvg() …    │
                 └───────────────┬────────────────────────────┘
                                 │ builds
                                 ▼
                 ┌────────────────────────────────────────────┐
                 │ ChartSpec  (backend-agnostic description:   │
                 │ series, points, labels, axes, legend, theme)│
                 └───────────────┬────────────────────────────┘
   Chart-type builders ──────────┘            │ consumed by
   (pie, donut, bar, line, area)              ▼
                 ┌────────────────────────────────────────────┐
                 │ RendererInterface  (picked by Format)       │
                 │  • SvgRenderer  → SVG       (done)          │
                 │  • GdRenderer   → PNG/JPEG/GIF/WebP (next)  │
                 └───────────────┬────────────────────────────┘
                                 ▼
                 ┌────────────────────────────────────────────┐
                 │ RenderedChart (bytes + mimeType)            │
                 │  toBinary() · save() · toBase64()           │
                 │  toDataUri() · toHtmlImg() · __toString()   │
                 └────────────────────────────────────────────┘
```

Chart types only build a `ChartSpec`; backends only render a `ChartSpec` into a `Format`.
A small renderer registry maps the requested format to a capable backend (SVG→`SvgRenderer`,
raster→`GdRenderer`).

---

## Done so far  ✅

- **Phase 0 — Stabilize.** Fixed fatal parse/PSR-4 errors; PHPUnit + PHPStan (lvl 6) + CI-ready
  tooling. Loads and builds.
- **Phase 1 — Architecture.** PHP floor 8.1. `Output\{Format,RenderedChart}`,
  `Spec\{ChartType,DataPoint,Series,ChartSpec,Theme,LegendPosition}`,
  `Rendering\{RendererInterface,AbstractRenderer}`, typed `Exception\*`.
- **SVG backend.** `Rendering\SvgRenderer` for **pie, donut, bar, line, area** — legend,
  axis scaling, escaped labels. Proven end-to-end as inline data-URI `<img>` in the playground.
- **GD raster backend (Phase 2).** `Rendering\GdRenderer` → PNG/JPEG/GIF/WebP for the same 5
  types; shared `Rendering\PlotData` keeps both backends consistent.
- **Fluent facade (Phase 3).** `Chart::pie($data)->title(...)->toEmailImg()` etc., backed by a
  `RendererRegistry`. Legacy Phase-0 skeleton removed — one clean API.

_Current: 52 tests / 134 assertions green, PHPStan level 6 clean, on branch `roadmap-rework`._

---

## Phase 2 — GD raster backend (PNG/JPEG/GIF/WebP)  · ✅ DONE

Replaces the old jpgraph plan. A `Rendering\GdRenderer` using the standard `gd` extension —
no external chart library, no fork. **Priority, because PNG is what makes the email use case
work across all clients.**

- `GdRenderer extends AbstractRenderer`, `supportedFormats = [Png, Jpeg, Gif, Webp]`.
- Implement the **core 5 types** (pie, donut, bar, line, area) to match the SVG backend.
- Detect a missing `gd` extension and throw a clear exception (GD is documented/`suggest`ed,
  not a hard composer requirement, so SVG-only users aren't forced to install it).
- Tests assert real image output (mime/signature/dimensions, non-empty), plus base64/data-URI.

**Done when:** the same `ChartSpec` renders to PNG and SVG by changing one argument, and a
PNG chart round-trips through `RenderedChart` to a data-URI `<img>`.

## Phase 3 — Fluent facade (developer experience)  · ✅ DONE

Delivered `Chart` (factories pie/donut/bar/line/area; fluent
`title/categories/size/legend/palette/background/addSeries`; output
`toSvg/toPng/toJpeg/toDataUri/toHtmlImg/toEmailImg/save/render`), a `RendererRegistry`
(format→backend, GD lazy), and immutable `Theme::with*` helpers. The legacy Phase-0
Strategy skeleton (`ChartHandler`, `AbstractChart`, `Charts\*`, `Strategies\*`) was removed
so the package has one clean entry point. Playground `GET /charts` now built via the facade.

A high-level API so callers don't assemble `ChartSpec` + renderer by hand.

```php
Chart::pie(['Chrome' => 63, 'Firefox' => 19])->title('Share')->toEmailImg();   // PNG <img>
Chart::bar($values)->categories($months)->toPng();                              // raw PNG bytes
Chart::line($series)->toSvg();                                                  // SVG string
Chart::donut($data)->toDataUri(Format::Png);                                    // data: URI
```

- Builder methods per type; `title()/categories()/theme()/legend()`.
- Output methods: `toSvg()`, `toPng()/toJpeg()`, `toDataUri()`, `toHtmlImg()`,
  `save($path)` (format inferred from extension), and `toEmailImg()` (PNG `<img>` by default).
- Renderer registry resolves format → backend under the hood.

**Done when:** every core type is reachable through the facade for both SVG and PNG.

## Phase 4 — Robustness & DX polish

Theme presets (light/dark/brand), friendlier defaults, validation/exception coverage review,
and the email-embedding recipe documented as a first-class path.

## Phase 5 — Docs, examples, CI & release v1.0  · ✅ DONE (pending tag)

README, `examples/`, `LICENSE` (MIT), `CHANGELOG.md` (v1.0.0), and GitHub Actions CI
(`composer validate` + PHPStan + PHPUnit on PHP 8.1–8.3 with gd) are in place. Remaining to
cut the release: merge `roadmap-rework` → `main`, tag `v1.0.0`, and submit to Packagist.

Ship a real, polished release with the core 5 types on both backends + the facade, **before**
expanding chart types.

- Full `README.md`: install, quickstart, the core types, output formats, the offline-email /
  base64 recipe (with the SVG-vs-PNG caveat), and the extension guide.
- `examples/` with runnable scripts; `LICENSE`; `CHANGELOG.md`; semver.
- GitHub Actions CI: lint + PHPStan + tests across PHP 8.1–8.3.
- Tag and publish to Packagist → **v1.0.0**.

## Phase 6 — Post-1.0: remaining chart types

Add **stacked bar** and **scatter** (and any future types) across both backends, following
the documented "add a new chart type" recipe. Release as 1.x.

---

## Dropped / changed from the original plan

- ❌ **jpgraph raster backend** — replaced by the built-in `GdRenderer` (no external fork/dep).
- ❌ **JS-config backend (Chart.js/ECharts)** — removed from scope.
- 🔀 **Release pulled earlier** — v1.0 ships the core 5 types; stacked/scatter follow in 1.x.
- ➕ **Fluent facade** added as a first-class phase.

## Cross-cutting notes

- **GD vs SVG for email:** SVG is the web default; the email helper defaults to PNG for
  client compatibility.
- **`ext-gd`** is required only for the GD backend — documented and `suggest`ed, guarded at
  runtime, not a hard composer dependency.
