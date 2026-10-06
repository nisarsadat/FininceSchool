<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\TeacherSalaryPayment;
use App\Services\FinanceService;
use App\Support\SolarHijri;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TeacherSalaryController extends Controller
{
    public function index(Request $request, FinanceService $finance)
    {
        $year = $finance->resolveYear($request->integer('year') ?: null);

        $payments = TeacherSalaryPayment::query()
            ->with(['teacher', 'account'])
            ->where('hijri_year', $year)
            ->when($request->filled('month'), fn ($query) => $query->where('hijri_month', $request->integer('month')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->whereHas('teacher', fn ($teacher) => $teacher->where('name', 'like', "%{$search}%"));
            })
            ->orderByDesc('hijri_month')
            ->orderByDesc('receipt_day')
            ->get();

        return response()->json(['data' => $payments, 'year' => $year]);
    }

    public function ledger(Request $request, Teacher $teacher, FinanceService $finance)
    {
        $year = $finance->resolveYear($request->integer('year') ?: null);

        return response()->json($finance->teacherLedger($teacher, $year));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'hijri_year' => ['required', 'integer', 'between:1300,1500'],
            'hijri_month' => ['required', 'integer', 'between:1,12'],
            'receipt_year' => ['required', 'integer', 'between:1300,1500'],
            'receipt_month' => ['required', 'integer', 'between:1,12'],
            'receipt_day' => ['required', 'integer', 'between:1,31'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_id' => ['required', 'exists:accounts,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $exists = TeacherSalaryPayment::query()
            ->where('teacher_id', $data['teacher_id'])
            ->where('hijri_year', $data['hijri_year'])
            ->where('hijri_month', $data['hijri_month'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'hijri_month' => 'Salary for this month is already paid.',
            ]);
        }

        try {
            $payment = TeacherSalaryPayment::create([
                'teacher_id' => $data['teacher_id'],
                'hijri_year' => $data['hijri_year'],
                'hijri_month' => $data['hijri_month'],
                'receipt_year' => $data['receipt_year'],
                'receipt_month' => $data['receipt_month'],
                'receipt_day' => $data['receipt_day'],
                'paid_on' => SolarHijri::validatedGregorian($data['receipt_year'], $data['receipt_month'], $data['receipt_day']),
                'amount' => $data['amount'],
                'account_id' => $data['account_id'],
                'notes' => blank($data['notes'] ?? null) ? null : $data['notes'],
            ]);
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'hijri_month' => 'Salary for this month is already paid.',
            ]);
        }

        return response()->json($payment->load(['teacher', 'account']), 201);
    }

    public function destroy(TeacherSalaryPayment $salary)
    {
        $salary->delete();

        return response()->json(['message' => 'Salary payment removed.']);
    }
}
