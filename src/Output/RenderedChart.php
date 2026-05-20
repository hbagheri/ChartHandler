<?php

namespace HBVSoft\ChartHandler\Output;

use Stringable;

/**
 * The result of rendering a chart: the raw output bytes plus the format they are in.
 *
 * This is the single type every backend returns, so callers get a uniform set of
 * output targets regardless of which renderer produced the chart — including the
 * base64 / data-URI helpers used to embed charts inline in offline HTML emails.
 */
final class RenderedChart implements Stringable
{
    public function __construct(
        private readonly string $bytes,
        private readonly Format $format,
    ) {
    }

    public function format(): Format
    {
        return $this->format;
    }

    public function mimeType(): string
    {
        return $this->format->mimeType();
    }

    /**
     * Raw output bytes (PNG/JPEG binary, or SVG markup).
     */
    public function toBinary(): string
    {
        return $this->bytes;
    }

    public function toBase64(): string
    {
        return base64_encode($this->bytes);
    }

    /**
     * `data:` URI suitable for an <img src="..."> with no external request — the
     * reliable way to show charts in HTML emails opened without internet access.
     */
    public function toDataUri(): string
    {
        return 'data:' . $this->mimeType() . ';base64,' . $this->toBase64();
    }

    /**
     * A complete, self-contained <img> tag using the data-URI as its source.
     *
     * @param array<string, string|int> $attributes extra HTML attributes (e.g. alt, width, class)
     */
    public function toHtmlImg(array $attributes = []): string
    {
        $attributes = ['src' => $this->toDataUri()] + $attributes;

        $rendered = [];
        foreach ($attributes as $name => $value) {
            $rendered[] = sprintf(
                '%s="%s"',
                $name,
                htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'),
            );
        }

        return '<img ' . implode(' ', $rendered) . ' />';
    }

    public function save(string $path): bool
    {
        return file_put_contents($path, $this->bytes) !== false;
    }

    /**
     * Size of the output in bytes.
     */
    public function size(): int
    {
        return strlen($this->bytes);
    }

    public function __toString(): string
    {
        return $this->bytes;
    }
}
