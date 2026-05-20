<?php

namespace HBVSoft\ChartHandler\Spec;

enum LegendPosition
{
    case None;
    case Top;
    case Right;
    case Bottom;
    case Left;

    public function isVisible(): bool
    {
        return $this !== self::None;
    }
}
