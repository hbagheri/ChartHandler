# ChartHandler — Roadmap

> A clean, extensible, **public Composer package** for generating charts in PHP.

## Vision

ChartHandler turns data into charts through a fluent, backend-agnostic API:

- **Pluggable rendering backends** — jpgraph (raster), native SVG (pure PHP), and
  optionally a JS-config backend (Chart.js/ECharts) for client-side rendering.
- **User-selectable output format** — PNG, JPEG, GIF, WebP, SVG.
- **Multiple output targets** — raw binary, save to file, **base64 / data-URI**, and a
  ready-to-embed `<img>` tag.
- **Offline-email use case is first-class** — generate charts as base64 `data:` URIs so
  they render inside HTML emails with no internet access / no external `src` fetch.
- **Extensible chart-type system** — pie, donut, bar, stacked bar, line, area, scatter,
  with a documented recipe to add new chart types without touching the backends.

---

## Target architecture

```
                 ┌────────────────────────────────────────────┐
   Your code ──► │ ChartHandler (facade / fluent API)          │
                 └───────────────┬────────────────────────────┘
                                 │ builds
                                 ▼
                 ┌────────────────────────────────────────────┐
                 │ ChartSpec  (backend-agnostic description:   │
                 │ series, points, labels, axes, legend, theme)│
                 └───────────────┬────────────────────────────┘
   Chart types build the spec ───┘            │ consumed by
   (PieChart, BarChart, LineChart…)           ▼
                 ┌────────────────────────────────────────────┐
                 │ RendererInterface  (backend)                │
                 │  • JpGraphRenderer  → PNG/JPEG/GIF          │
                 │  • SvgRenderer      → SVG                   │
                 │  • (JsConfigRenderer → JSON, optional)      │
                 └───────────────┬────────────────────────────┘
                                 ▼
                 ┌────────────────────────────────────────────┐
                 │ RenderedChart (bytes + mimeType)            │
                 │  toBinary() · save() · toBase64()           │
                 │  toDataUri() · toHtmlImg() · __toString()   │
                 └────────────────────────────────────────────┘
```

**Key design idea:** chart types only know how to build a normalized `ChartSpec`; backends
only know how to render a `ChartSpec` into a chosen `Format`. This decouples the
*type × backend* matrix so adding a new chart type doesn't require editing every backend.

---

## Phase 0 — Stabilize (make it load & build)  · _blocking_

The project currently has fatal parse/autoload errors and cannot run.

1. Fix the `+-` parse error in `ChartStrategyInterface` namespace
   (`src/Strategies/ChartStrategyInterface.php:3`).
2. Reconcile namespaces ↔ directories for PSR-4 (pick canonical layout, move files).
   Today `src/Strategies/*` declares namespace `...\Charts\Strategies` → mismatch.
3. Fix wrong imports: `HBVSoft\Charts\...` → `HBVSoft\ChartHandler\...`
   (`AbstractChart.php:4`, `NonAxisChart.php:6,8`).
4. Decide PHP floor: bump `>=8.0` → `>=8.1` to allow native `enum` for `Format`
   (or use class constants and keep 8.0).
5. Add dev tooling: PHPUnit, PHPStan (max), PHP-CS-Fixer/PHPCS, `.gitignore`.
6. Add a smoke test that autoloads and renders a trivial chart.

**Done when:** `composer install` succeeds, `php -l` is clean on all files, smoke test passes.

## Phase 1 — Architecture & contracts (design spike)

Lock the core abstractions before building features.

- Define `ChartSpec` value object (series, points, labels, axes, legend, theme/options).
- Define `Format` enum and `RenderedChart` value object with
  `toBinary / toBase64 / toDataUri / toHtmlImg / save / __toString`.
- Define `RendererInterface::render(ChartSpec, Format): RenderedChart` plus a capability
  declaration (which formats each backend supports — jpgraph can't emit SVG, etc.).
- Define a `Theme`/options object (size, colors, fonts, legend position).
- Write short ADR notes for the type×backend decision and PHP-version decision.

**Done when:** interfaces + value objects exist with unit tests; no rendering yet.

## Phase 2 — First vertical slice (pie, end-to-end)

Prove the full pipeline with one type and one backend.

- `PieChart` → builds `ChartSpec`.
- `JpGraphRenderer` renders pie → PNG, with format selection (PNG/JPEG).
- Wire all output targets: binary, file, base64, data-URI, `<img>` tag.
- Tests: mime/dimensions/non-empty assertions + a golden-file comparison.

**Done when:** can generate a pie PNG, save it, get a base64 data-URI, and embed it in HTML.

## Phase 3 — SVG backend

Second backend to validate the abstraction (pure PHP, no GD dependency).

- `SvgRenderer` for pie; SVG as a first-class `Format`.
- base64/data-URI works for SVG too (`data:image/svg+xml;base64,…`).

**Done when:** the same pie chart renders to PNG (jpgraph) **and** SVG by changing one arg.

## Phase 4 — Chart-type coverage

- Implement: **bar, stacked bar, line, area, scatter, donut** — each as a `ChartSpec`
  builder, working across both backends.
- Document the **"add a new chart type" recipe** (the template) with a tutorial + example.

**Done when:** every type has tests on both backends; the extension guide is verified by
adding a throwaway custom type end-to-end.

## Phase 5 — Robustness & developer experience

- Input validation + typed exceptions (empty data, label/data length mismatch,
  unsupported format×backend combo).
- Sane defaults, fluent-API polish, theme presets (light/dark/brand).
- Email-embedding helper documented as a first-class recipe.

## Phase 6 — Docs, CI & release (public package)

- Full `README.md`: install, quickstart, each chart type, output formats,
  the offline-email/base64 recipe, and the extension guide.
- `examples/` with runnable scripts; `LICENSE`; `CHANGELOG.md`; semver.
- GitHub Actions CI: lint + PHPStan + tests across PHP 8.1–8.3.
- Publish to Packagist; tag `v0.x` → stabilize → `v1.0.0`.

**Done when:** CI is green, tagged release installable via
`composer require hbvsoft/charthandler`.

## Phase 7 — Optional: JS-config backend

Emit Chart.js/ECharts JSON config from the same `ChartSpec`, for consumers who want
interactive client-side charts instead of a static image.

---

## Cross-cutting decisions to confirm

- **PHP floor:** 8.0 (class constants) vs 8.1 (native enums). _Recommended: 8.1._
- **jpgraph fork:** confirm `hbvsoft/jpgraph ^1.0` is published & installable; it requires
  the GD extension at runtime.
- **Type×backend strategy:** normalized `ChartSpec` (recommended) vs per-backend
  per-type methods.
