<?php

namespace HBVSoft\ChartHandler\Output;

use HBVSoft\ChartHandler\Exception\UnsupportedFormatException;

/**
 * Output image formats a renderer can produce.
 */
enum Format: string
{
    case Png = 'png';
    case Jpeg = 'jpeg';
    case Gif = 'gif';
    case Webp = 'webp';
    case Svg = 'svg';

    public function mimeType(): string
    {
        return match ($this) {
            self::Png => 'image/png',
            self::Jpeg => 'image/jpeg',
            self::Gif => 'image/gif',
            self::Webp => 'image/webp',
            self::Svg => 'image/svg+xml',
        };
    }

    /**
     * Canonical file extension (without the dot).
     */
    public function extension(): string
    {
        return match ($this) {
            self::Jpeg => 'jpg',
            default => $this->value,
        };
    }

    public function isVector(): bool
    {
        return $this === self::Svg;
    }

    public function isRaster(): bool
    {
        return ! $this->isVector();
    }

    /**
     * Resolve a Format from a file extension or mime type (case-insensitive).
     *
     * @throws UnsupportedFormatException when the value maps to no known format
     */
    public static function fromExtension(string $value): self
    {
        $normalized = strtolower(ltrim(trim($value), '.'));

        return match ($normalized) {
            'png', 'image/png' => self::Png,
            'jpg', 'jpeg', 'image/jpeg' => self::Jpeg,
            'gif', 'image/gif' => self::Gif,
            'webp', 'image/webp' => self::Webp,
            'svg', 'svg+xml', 'image/svg+xml' => self::Svg,
            default => throw UnsupportedFormatException::forExtension($value),
        };
    }
}
