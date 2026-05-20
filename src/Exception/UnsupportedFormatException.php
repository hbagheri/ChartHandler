<?php

namespace HBVSoft\ChartHandler\Exception;

use HBVSoft\ChartHandler\Output\Format;
use InvalidArgumentException;

class UnsupportedFormatException extends InvalidArgumentException implements ChartHandlerException
{
    public static function forExtension(string $value): self
    {
        return new self(sprintf('Unknown image format "%s".', $value));
    }

    public static function notSupportedByRenderer(Format $format, string $renderer): self
    {
        return new self(sprintf(
            'Renderer "%s" cannot produce %s output.',
            $renderer,
            strtoupper($format->value),
        ));
    }
}
