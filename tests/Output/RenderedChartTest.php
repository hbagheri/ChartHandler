<?php

namespace HBVSoft\ChartHandler\Tests\Output;

use HBVSoft\ChartHandler\Output\Format;
use HBVSoft\ChartHandler\Output\RenderedChart;
use PHPUnit\Framework\TestCase;

class RenderedChartTest extends TestCase
{
    public function test_binary_base64_and_data_uri(): void
    {
        $chart = new RenderedChart('PNGDATA', Format::Png);

        self::assertSame('PNGDATA', $chart->toBinary());
        self::assertSame('PNGDATA', (string) $chart);
        self::assertSame(base64_encode('PNGDATA'), $chart->toBase64());
        self::assertSame(
            'data:image/png;base64,' . base64_encode('PNGDATA'),
            $chart->toDataUri(),
        );
        self::assertSame(7, $chart->size());
        self::assertSame(Format::Png, $chart->format());
    }

    public function test_html_img_embeds_data_uri_and_escapes_attributes(): void
    {
        $html = (new RenderedChart('X', Format::Png))->toHtmlImg([
            'alt' => 'Sales "2025"',
            'width' => 600,
        ]);

        self::assertStringStartsWith('<img src="data:image/png;base64,', $html);
        self::assertStringContainsString('alt="Sales &quot;2025&quot;"', $html);
        self::assertStringContainsString('width="600"', $html);
        self::assertStringEndsWith(' />', $html);
    }

    public function test_save_writes_bytes(): void
    {
        $path = sys_get_temp_dir() . '/charthandler_rc_' . uniqid() . '.png';

        try {
            self::assertTrue((new RenderedChart('BYTES', Format::Png))->save($path));
            self::assertStringEqualsFile($path, 'BYTES');
        } finally {
            @unlink($path);
        }
    }
}
