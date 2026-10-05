<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FinanceService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, FinanceService $finance)
    {
        $year = $finance->resolveYear($request->integer('year') ?: null);

        return response()->json($finance->dashboard($year));
    }
}
