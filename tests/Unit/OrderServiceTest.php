<?php

namespace Tests\Unit;

use Illuminate\Support\Collection;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    public function test_calculates_totals_discount_and_change(): void
    {
        $svc = new \App\Services\OrderService();

        $lines = collect([
            ['price' => 28000, 'qty' => 2],  // 56000
            ['price' => 20000, 'qty' => 1],  // 20000
        ]);

        $calc = $svc->calculate($lines, discount: 6000, paid: 80000);

        $this->assertSame(76000, $calc['subtotal']);
        $this->assertSame(70000, $calc['total']);
        $this->assertSame(10000, $calc['change_amount']);
    }

    public function test_discount_cannot_exceed_subtotal(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new \App\Services\OrderService())->calculate(
            collect([['price' => 20000, 'qty' => 1]]),
            discount: 30000,
            paid: 20000
        );
    }

    public function test_paid_cannot_be_less_than_total(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new \App\Services\OrderService())->calculate(
            collect([['price' => 20000, 'qty' => 1]]),
            discount: 0,
            paid: 10000
        );
    }
}
