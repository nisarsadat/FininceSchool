<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

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

    /**
     * Factory-reset the desk: wipe every data table in one statement and
     * restore the original demo dataset. The schema (and the migrations
     * table) is left alone, so this stays a pair of round trips instead of a
     * full drop-and-recreate that would time out in a serverless request.
     *
     * All personal access tokens are discarded with the data, so everyone is
     * signed out — including whoever pressed the button.
     */
    public function reset()
    {
        $tables = DB::select(
            "SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename <> 'migrations'"
        );

        if ($tables !== []) {
            $list = implode(', ', array_map(
                fn ($table) => '"'.$table->tablename.'"',
                $tables
            ));

            DB::statement("TRUNCATE TABLE {$list} RESTART IDENTITY CASCADE");
        }

        $exit = Artisan::call('db:seed', ['--force' => true]);

        if ($exit !== 0) {
            return response()->json(['message' => 'Reset failed.'], 500);
        }

        return response()->json(['reset' => true]);
    }
}
