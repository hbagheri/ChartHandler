<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use HBVSoft\ChartHandler\Chart;
use HBVSoft\ChartHandler\Spec\Series;

$out = __DIR__ . '/out';
@mkdir($out);

// Build a self-contained HTML email body: every chart is an inline base64 PNG <img>,
// so it renders with no internet access and in clients that don't support SVG.
$revenue = Chart::bar([12, 19, 7, 22, 15])
    ->title('Monthly revenue')
    ->categories(['Jan', 'Feb', 'Mar', 'Apr', 'May'])
    ->toEmailImg(['alt' => 'Monthly revenue', 'width' => 480]);

$signups = Chart::line([Series::fromValues('2024', [10, 14, 9, 18])])
    ->addSeries(Series::fromValues('2025', [13, 11, 17, 21]))
    ->title('Signups')
    ->categories(['Q1', 'Q2', 'Q3', 'Q4'])
    ->toEmailImg(['alt' => 'Signups', 'width' => 480]);

$html = <<<HTML
<!doctype html>
<html><body style="font-family:sans-serif">
  <h1>Your monthly report</h1>
  {$revenue}
  {$signups}
</body></html>
HTML;

file_put_contents($out . '/email.html', $html);

echo "Wrote {$out}/email.html (open it; the charts are embedded, no external requests)\n";
