<?php

namespace HBVSoft\ChartHandler\Rendering\Svg;

/**
 * Tiny helper that accumulates SVG fragments and wraps them in a document.
 * Keeps the renderer free of string-escaping and number-formatting noise.
 */
final class SvgCanvas
{
    /** @var list<string> */
    private array $elements = [];

    public function __construct(
        private readonly int $width,
        private readonly int $height,
        private readonly ?string $background,
        private readonly string $fontFamily,
    ) {
    }

    public function add(string $fragment): self
    {
        $this->elements[] = $fragment;

        return $this;
    }

    public function rect(float $x, float $y, float $w, float $h, string $fill, ?string $extra = null): self
    {
        return $this->add(sprintf(
            '<rect x="%s" y="%s" width="%s" height="%s" fill="%s"%s />',
            self::num($x),
            self::num($y),
            self::num($w),
            self::num($h),
            self::esc($fill),
            $extra !== null ? ' ' . $extra : '',
        ));
    }

    public function circle(float $cx, float $cy, float $r, string $fill, ?string $extra = null): self
    {
        return $this->add(sprintf(
            '<circle cx="%s" cy="%s" r="%s" fill="%s"%s />',
            self::num($cx),
            self::num($cy),
            self::num($r),
            self::esc($fill),
            $extra !== null ? ' ' . $extra : '',
        ));
    }

    public function line(float $x1, float $y1, float $x2, float $y2, string $stroke, float $width = 1.0): self
    {
        return $this->add(sprintf(
            '<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="%s" stroke-width="%s" />',
            self::num($x1),
            self::num($y1),
            self::num($x2),
            self::num($y2),
            self::esc($stroke),
            self::num($width),
        ));
    }

    /**
     * @param array<string, string> $attributes
     */
    public function text(float $x, float $y, string $content, array $attributes = []): self
    {
        $attr = '';
        foreach ($attributes as $name => $value) {
            $attr .= sprintf(' %s="%s"', $name, self::esc($value));
        }

        return $this->add(sprintf(
            '<text x="%s" y="%s"%s>%s</text>',
            self::num($x),
            self::num($y),
            $attr,
            self::esc($content),
        ));
    }

    public function render(string $title = ''): string
    {
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" font-family="%s">',
            $this->width,
            $this->height,
            $this->width,
            $this->height,
            self::esc($this->fontFamily),
        );

        if ($title !== '') {
            $svg .= '<title>' . self::esc($title) . '</title>';
        }

        if ($this->background !== null) {
            $svg .= sprintf(
                '<rect x="0" y="0" width="%d" height="%d" fill="%s" />',
                $this->width,
                $this->height,
                self::esc($this->background),
            );
        }

        return $svg . implode('', $this->elements) . '</svg>';
    }

    public static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Compact, locale-independent number formatting (no trailing zeros).
     */
    public static function num(float $value): string
    {
        if (is_nan($value) || is_infinite($value)) {
            $value = 0.0;
        }

        $formatted = number_format($value, 3, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }
}
