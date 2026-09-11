<?php

namespace App\Support;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class QrCode
{
    /** @return string Data-URI PNG */
    public static function dataUri(string $content, int $size = 160): string
    {
        $builder = new Builder(
            writer: new PngWriter,
            data: $content,
            size: $size,
            margin: 0,
        );

        return $builder->build()->getDataUri();
    }
}
