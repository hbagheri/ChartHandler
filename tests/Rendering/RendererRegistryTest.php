<?php

namespace HBVSoft\ChartHandler\Tests\Rendering;

use HBVSoft\ChartHandler\Output\Format;
use HBVSoft\ChartHandler\Rendering\GdRenderer;
use HBVSoft\ChartHandler\Rendering\RendererRegistry;
use HBVSoft\ChartHandler\Rendering\SvgRenderer;
use PHPUnit\Framework\TestCase;

class RendererRegistryTest extends TestCase
{
    public function test_svg_format_resolves_to_svg_renderer(): void
    {
        self::assertInstanceOf(SvgRenderer::class, (new RendererRegistry())->rendererFor(Format::Svg));
    }

    public function test_raster_formats_resolve_to_gd_renderer(): void
    {
        if (! extension_loaded('gd')) {
            self::markTestSkipped('gd not loaded');
        }

        $registry = new RendererRegistry();
        self::assertInstanceOf(GdRenderer::class, $registry->rendererFor(Format::Png));
        self::assertInstanceOf(GdRenderer::class, $registry->rendererFor(Format::Jpeg));
    }

    public function test_injected_renderers_are_returned_and_cached(): void
    {
        $svg = new FakeRenderer();
        $registry = new RendererRegistry(svg: $svg);

        self::assertSame($svg, $registry->rendererFor(Format::Svg));
        self::assertSame($svg, $registry->rendererFor(Format::Svg));
    }
}
