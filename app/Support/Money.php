<?php

namespace App\Support;

class Money
{
    public static function rupiah(int $v): string
    {
        return 'Rp '.number_format($v, 0, ',', '.');
    }
}
