<?php

namespace Tests\Unit;

use App\Support\SolarHijri;
use PHPUnit\Framework\TestCase;

class SolarHijriTest extends TestCase
{
    public function test_afghan_month_names_are_solar_hijri(): void
    {
        $this->assertSame([
            'حمل', 'ثور', 'جوزا', 'سرطان', 'اسد', 'سنبله',
            'میزان', 'عقرب', 'قوس', 'جدی', 'دلو', 'حوت',
        ], array_values(SolarHijri::MONTHS));
    }

    public function test_nowruz_dates_convert_both_ways(): void
    {
        $this->assertSame('2026-03-21', SolarHijri::toGregorian(1405, 1, 1));
        $this->assertSame(1405, SolarHijri::fromGregorian(2026, 3, 21)['year']);
        $this->assertSame(1, SolarHijri::fromGregorian(2026, 3, 21)['month']);
        $this->assertSame(1, SolarHijri::fromGregorian(2026, 3, 21)['day']);
        $this->assertSame('حمل', SolarHijri::fromGregorian(2026, 3, 21)['month_name']);

        $this->assertSame('2025-03-21', SolarHijri::toGregorian(1404, 1, 1));
        $this->assertSame('2024-03-20', SolarHijri::toGregorian(1403, 1, 1));
    }

    public function test_month_lengths(): void
    {
        $this->assertSame(31, SolarHijri::monthLength(1405, 1));
        $this->assertSame(30, SolarHijri::monthLength(1405, 7));
        $this->assertContains(SolarHijri::monthLength(1405, 12), [29, 30]);
    }
}
