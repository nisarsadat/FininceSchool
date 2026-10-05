<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\SolarHijri;

class CalendarController extends Controller
{
    public function __invoke()
    {
        $today = SolarHijri::today();
        $years = [];

        for ($year = $today['year'] - 3; $year <= $today['year'] + 1; $year++) {
            $years[] = $year;
        }

        $setting = Setting::current();

        return response()->json([
            'today' => $today,
            'months' => SolarHijri::months(),
            'years' => $years,
            'school_name' => $setting->school_name,
            'default_locale' => $setting->default_locale ?: 'en',
            'default_theme' => $setting->default_theme ?: 'light',
            'currency' => 'اف',
        ]);
    }
}
