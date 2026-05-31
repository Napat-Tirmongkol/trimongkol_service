<?php

namespace Tests\Unit\Accounting;

use App\Services\Accounting\Money;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_parses_and_renders_minor_units(): void
    {
        $this->assertSame(10050, Money::toMinor('100.50'));
        $this->assertSame(10000, Money::toMinor('100'));
        $this->assertSame(10000, Money::toMinor(100));
        $this->assertSame(50, Money::toMinor('0.5'));
        $this->assertSame(-2575, Money::toMinor('-25.75'));

        $this->assertSame('100.50', Money::fromMinor(10050));
        $this->assertSame('0.00', Money::fromMinor(0));
        $this->assertSame('-25.75', Money::fromMinor(-2575));
    }

    public function test_percentage_rounds_half_up(): void
    {
        $this->assertSame(700, Money::percentage(10000, '7.000'));   // 7% of 100.00 = 7.00
        $this->assertSame(233, Money::percentage(3333, '7.000'));    // 7% of 33.33 = 2.3331 -> 2.33
        $this->assertSame(300, Money::percentage(10000, '3'));       // 3% of 100.00 = 3.00
        $this->assertSame(0, Money::percentage(0, '7'));
    }

    public function test_line_amount_multiplies_quantity_by_price(): void
    {
        $this->assertSame(10000, Money::lineAmount(1, '100'));        // 1 × 100.00
        $this->assertSame(25000, Money::lineAmount('2.5', '100'));    // 2.5 × 100.00 = 250.00
        $this->assertSame(333, Money::lineAmount(3, '1.111'));        // 3 × 1.111 = 3.333 -> 3.33
    }
}
