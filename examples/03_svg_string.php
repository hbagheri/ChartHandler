<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use HBVSoft\ChartHandler\Chart;
use HBVSoft\ChartHandler\Spec\LegendPosition;

$out = __DIR__ . '/out';
@mkdir($out);

// SVG needs no extensions at all — pure PHP.
$svg = Chart::donut(['Linux' => 45, 'Windows' => 35, 'macOS' => 20])
    ->title('OS split')
    ->legend(LegendPosition::Right)
    ->toSvg();

file_put_contents($out . '/os-split.svg', $svg);

echo "Wrote {$out}/os-split.svg (" . strlen($svg) . " bytes of SVG)\n";
