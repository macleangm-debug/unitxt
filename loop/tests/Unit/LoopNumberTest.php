<?php

namespace Tests\Unit;

use App\Support\LoopNumber;
use PHPUnit\Framework\TestCase;

class LoopNumberTest extends TestCase
{
    public function test_formats_thousands_while_keeping_raw_values_parseable(): void
    {
        $this->assertSame('10,000', LoopNumber::format(10000));
        $this->assertSame('1,000', LoopNumber::format(1000));
        $this->assertSame(10000.0, LoopNumber::parse('10,000'));
        $this->assertSame(10000.0, LoopNumber::parse(10000));
        $this->assertSame('TZS 10,000', LoopNumber::money(10000, 'TZS'));
        $this->assertSame('USD 10,000.00', LoopNumber::money(10000, 'USD'));
        $this->assertSame(0, LoopNumber::decimals('TZS'));
        $this->assertSame(2, LoopNumber::decimals('USD'));
    }
}
