<?php

namespace HBVSoft\ChartHandler\Spec;

/**
 * Backend-agnostic presentation options. Renderers map these onto their own drawing
 * primitives; values they cannot honour are ignored rather than erroring.
 */
final class Theme
{
    /** @var list<string> */
    public const DEFAULT_PALETTE = [
        '#4e79a7', '#f28e2b', '#e15759', '#76b7b2', '#59a14f',
        '#edc948', '#b07aa1', '#ff9da7', '#9c755f', '#bab0ac',
    ];

    /**
     * @param list<string> $palette ordered hex colors used for series/slices
     */
    public function __construct(
        public readonly int $width = 600,
        public readonly int $height = 400,
        public readonly array $palette = self::DEFAULT_PALETTE,
        public readonly ?string $background = '#ffffff',
        public readonly string $fontFamily = 'sans-serif',
        public readonly LegendPosition $legend = LegendPosition::Right,
    ) {
    }

    /**
     * Color for the nth series/slice, cycling through the palette.
     */
    public function colorAt(int $index): string
    {
        $palette = $this->palette === [] ? self::DEFAULT_PALETTE : $this->palette;

        return $palette[$index % count($palette)];
    }
}
