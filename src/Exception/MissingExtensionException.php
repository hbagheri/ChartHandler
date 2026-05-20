<?php

namespace HBVSoft\ChartHandler\Exception;

use RuntimeException;

class MissingExtensionException extends RuntimeException implements ChartHandlerException
{
    public static function forRenderer(string $extension, string $renderer): self
    {
        return new self(sprintf(
            'The "%s" PHP extension is required by %s but is not loaded.',
            $extension,
            $renderer,
        ));
    }
}
