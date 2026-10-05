<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function show()
    {
        return response()->json(array_merge(Setting::current()->toArray(), [
            'currency' => 'اف',
            'calendar' => 'Afghan Solar Hijri',
        ]));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'default_locale' => ['required', 'in:en,fa,ps'],
            'default_theme' => ['required', 'in:light,dark'],
        ]);

        $setting = Setting::current();
        $setting->update($data);

        return response()->json($setting);
    }
}
