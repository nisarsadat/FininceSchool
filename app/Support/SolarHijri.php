<?php

namespace App\Support;

use DateTimeImmutable;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SolarHijri
{
    public const MONTHS = [
        1 => 'حمل',
        2 => 'ثور',
        3 => 'جوزا',
        4 => 'سرطان',
        5 => 'اسد',
        6 => 'سنبله',
        7 => 'میزان',
        8 => 'عقرب',
        9 => 'قوس',
        10 => 'جدی',
        11 => 'دلو',
        12 => 'حوت',
    ];

    /**
     * Break points used by the standard Solar Hijri (Jalaali) conversion.
     *
     * @var list<int>
     */
    private const BREAKS = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];

    /**
     * @return list<array{number: int, name: string}>
     */
    public static function months(): array
    {
        $months = [];

        foreach (self::MONTHS as $number => $name) {
            $months[] = ['number' => $number, 'name' => $name];
        }

        return $months;
    }

    public static function monthName(int $month): string
    {
        if (! isset(self::MONTHS[$month])) {
            throw new InvalidArgumentException('Invalid Afghan month.');
        }

        return self::MONTHS[$month];
    }

    /**
     * @return array{year: int, month: int, day: int, month_name: string, formatted: string}
     */
    public static function today(): array
    {
        $now = new DateTimeImmutable('today');

        return self::fromGregorian(
            (int) $now->format('Y'),
            (int) $now->format('n'),
            (int) $now->format('j'),
        );
    }

    /**
     * @return array{year: int, month: int, day: int, month_name: string, formatted: string}
     */
    public static function fromGregorian(int $gy, int $gm, int $gd): array
    {
        $jalali = self::d2j(self::g2d($gy, $gm, $gd));

        return self::pack($jalali['jy'], $jalali['jm'], $jalali['jd']);
    }

    public static function toGregorian(int $jy, int $jm, int $jd): string
    {
        self::assertDate($jy, $jm, $jd);
        $gregorian = self::d2g(self::j2d($jy, $jm, $jd));

        return sprintf('%04d-%02d-%02d', $gregorian['gy'], $gregorian['gm'], $gregorian['gd']);
    }

    public static function monthLength(int $jy, int $jm): int
    {
        if ($jm < 1 || $jm > 12) {
            throw new InvalidArgumentException('Invalid Afghan month.');
        }

        if ($jm <= 6) {
            return 31;
        }

        if ($jm <= 11) {
            return 30;
        }

        return self::isLeap($jy) ? 30 : 29;
    }

    public static function isLeap(int $jy): bool
    {
        return self::jalCalLeap($jy) === 0;
    }

    public static function format(int $jy, int $jm, int $jd): string
    {
        return $jd.' '.self::monthName($jm).' '.$jy;
    }

    /**
     * @param  array{hijri_year: int, hijri_month: int, hijri_day: int}  $data
     */
    public static function gregorianFromParts(array $data): string
    {
        return self::validatedGregorian(
            (int) $data['hijri_year'],
            (int) $data['hijri_month'],
            (int) $data['hijri_day'],
        );
    }

    public static function validatedGregorian(int $year, int $month, int $day): string
    {
        try {
            return self::toGregorian($year, $month, $day);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([
                'hijri_day' => 'This day is not valid for the selected Afghan month.',
            ]);
        }
    }

    /**
     * @return array{year: int, month: int, day: int, month_name: string, formatted: string}
     */
    private static function pack(int $jy, int $jm, int $jd): array
    {
        return [
            'year' => $jy,
            'month' => $jm,
            'day' => $jd,
            'month_name' => self::monthName($jm),
            'formatted' => self::format($jy, $jm, $jd),
        ];
    }

    private static function assertDate(int $jy, int $jm, int $jd): void
    {
        if ($jm < 1 || $jm > 12 || $jd < 1 || $jd > self::monthLength($jy, $jm)) {
            throw new InvalidArgumentException('Invalid Afghan Solar Hijri date.');
        }
    }

    private static function jalCalLeap(int $jy): int
    {
        $breaks = self::BREAKS;
        $jp = $breaks[0];

        if ($jy < $jp || $jy >= $breaks[count($breaks) - 1]) {
            throw new InvalidArgumentException('Invalid Solar Hijri year '.$jy);
        }

        $jump = 0;

        for ($i = 1; $i < count($breaks); $i++) {
            $jm = $breaks[$i];
            $jump = $jm - $jp;

            if ($jy < $jm) {
                break;
            }

            $jp = $jm;
        }

        $n = $jy - $jp;

        if ($jump - $n < 6) {
            $n = $n - $jump + self::div($jump + 4, 33) * 33;
        }

        $leap = self::mod(self::mod($n + 1, 33) - 1, 4);

        if ($leap === -1) {
            $leap = 4;
        }

        return $leap;
    }

    /**
     * @return array{leap: int, gy: int, march: int}
     */
    private static function jalCal(int $jy): array
    {
        $breaks = self::BREAKS;
        $gy = $jy + 621;
        $leapJ = -14;
        $jp = $breaks[0];

        if ($jy < $jp || $jy >= $breaks[count($breaks) - 1]) {
            throw new InvalidArgumentException('Invalid Solar Hijri year '.$jy);
        }

        $jump = 0;

        for ($i = 1; $i < count($breaks); $i++) {
            $jm = $breaks[$i];
            $jump = $jm - $jp;

            if ($jy < $jm) {
                break;
            }

            $leapJ += self::div($jump, 33) * 8 + self::div(self::mod($jump, 33), 4);
            $jp = $jm;
        }

        $n = $jy - $jp;
        $leapJ += self::div($n, 33) * 8 + self::div(self::mod($n, 33) + 3, 4);

        if (self::mod($jump, 33) === 4 && $jump - $n === 4) {
            $leapJ++;
        }

        $leapG = self::div($gy, 4) - self::div((self::div($gy, 100) + 1) * 3, 4) - 150;
        $march = 20 + $leapJ - $leapG;

        if ($jump - $n < 6) {
            $n = $n - $jump + self::div($jump + 4, 33) * 33;
        }

        $leap = self::mod(self::mod($n + 1, 33) - 1, 4);

        if ($leap === -1) {
            $leap = 4;
        }

        return ['leap' => $leap, 'gy' => $gy, 'march' => $march];
    }

    private static function j2d(int $jy, int $jm, int $jd): int
    {
        $calendar = self::jalCal($jy);

        return self::g2d($calendar['gy'], 3, $calendar['march']) + ($jm - 1) * 31 - self::div($jm, 7) * ($jm - 7) + $jd - 1;
    }

    /**
     * @return array{jy: int, jm: int, jd: int}
     */
    private static function d2j(int $jdn): array
    {
        $gy = self::d2g($jdn)['gy'];
        $jy = $gy - 621;
        $calendar = self::jalCal($jy);
        $jdn1f = self::g2d($gy, 3, $calendar['march']);
        $k = $jdn - $jdn1f;

        if ($k >= 0) {
            if ($k <= 185) {
                return [
                    'jy' => $jy,
                    'jm' => 1 + self::div($k, 31),
                    'jd' => self::mod($k, 31) + 1,
                ];
            }

            $k -= 186;
        } else {
            $jy -= 1;
            $k += 179;

            if ($calendar['leap'] === 1) {
                $k += 1;
            }
        }

        return [
            'jy' => $jy,
            'jm' => 7 + self::div($k, 30),
            'jd' => self::mod($k, 30) + 1,
        ];
    }

    private static function g2d(int $gy, int $gm, int $gd): int
    {
        $d = self::div(($gy + self::div($gm - 8, 6) + 100100) * 1461, 4)
            + self::div(153 * self::mod($gm + 9, 12) + 2, 5)
            + $gd - 34840408;

        return $d - self::div(self::div($gy + 100100 + self::div($gm - 8, 6), 100) * 3, 4) + 752;
    }

    /**
     * @return array{gy: int, gm: int, gd: int}
     */
    private static function d2g(int $jdn): array
    {
        $j = 4 * $jdn + 139361631;
        $j += self::div(self::div(4 * $jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
        $i = self::div(self::mod($j, 1461), 4) * 5 + 308;
        $gd = self::div(self::mod($i, 153), 5) + 1;
        $gm = self::mod(self::div($i, 153), 12) + 1;
        $gy = self::div($j, 1461) - 100100 + self::div(8 - $gm, 6);

        return ['gy' => $gy, 'gm' => $gm, 'gd' => $gd];
    }

    private static function div(int $a, int $b): int
    {
        return intdiv($a, $b);
    }

    private static function mod(int $a, int $b): int
    {
        return $a - intdiv($a, $b) * $b;
    }
}
