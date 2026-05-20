<?php

namespace HBVSoft\ChartHandler\Rendering;

use HBVSoft\ChartHandler\Output\Format;

/**
 * Resolves the renderer that should handle a given output format. SVG goes to the
 * pure-PHP SvgRenderer; raster formats go to the GdRenderer.
 *
 * The GD renderer is created lazily, so SVG-only usage works on hosts without the
 * `gd` extension (GdRenderer's constructor throws when gd is missing).
 */
final class RendererRegistry
{
    private ?RendererInterface $svg;

    private ?RendererInterface $raster;

    public function __construct(?RendererInterface $svg = null, ?RendererInterface $raster = null)
    {
        $this->svg = $svg;
        $this->raster = $raster;
    }

    public function rendererFor(Format $format): RendererInterface
    {
        if ($format === Format::Svg) {
            return $this->svg ??= new SvgRenderer();
        }

        return $this->raster ??= new GdRenderer();
    }
}
