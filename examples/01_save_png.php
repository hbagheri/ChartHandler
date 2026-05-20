<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use HBVSoft\ChartHandler\Chart;

$out = __DIR__ . '/out';
@mkdir($out);

// Pie -> PNG (needs ext-gd)
Chart::pie(['Chrome' => 63, 'Firefox' => 19, 'Safari' => 12, 'Edge' => 6])
    ->title('Browser share')
    ->save($out . '/browser-share.png');

echo "Wrote {$out}/browser-share.png\n";
