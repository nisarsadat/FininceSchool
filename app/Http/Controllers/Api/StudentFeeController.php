<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentFeePayment;
use App\Services\FinanceService;
use App\Support\SolarHijri;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StudentFeeController extends Controller
{
    public function index(Request $request, FinanceService $finance)
    {
        $year = $finance->resolveYear($request->integer('year') ?: null);

        $payments = StudentFeePayment::query()
            ->with(['student.schoolClass', 'schoolClass', 'account'])
            ->where('hijri_year', $year)
            ->when($request->filled('month'), fn ($query) => $query->where('hijri_month', $request->integer('month')))
            ->when($request->filled('class_id'), fn ($query) => $query->where('class_id', $request->integer('class_id')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->whereHas('student', fn ($student) => $student->where('name', 'like', "%{$search}%"));
            })
            ->orderByDesc('hijri_month')
            ->orderByDesc('hijri_day')
            ->get();

        return response()->json(['data' => $payments, 'year' => $year]);
    }

    public function ledger(Request $request, Student $student, FinanceService $finance)
    {
        $year = $finance->resolveYear($request->integer('year') ?: null);

        return response()->json($finance->studentLedger($student, $year));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'hijri_year' => ['required', 'integer', 'between:1300,1500'],
            'hijri_month' => ['required', 'integer', 'between:1,12'],
            'receipt_year' => ['required', 'integer', 'between:1300,1500'],
            'receipt_month' => ['required', 'integer', 'between:1,12'],
            'receipt_day' => ['required', 'integer', 'between:1,31'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_id' => ['required', 'exists:accounts,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $student = Student::query()->findOrFail($data['student_id']);

        if (! $student->class_id) {
            throw ValidationException::withMessages([
                'student_id' => 'Assign this student to a class before recording a fee.',
            ]);
        }

        $exists = StudentFeePayment::query()
            ->where('student_id', $student->id)
            ->where('hijri_year', $data['hijri_year'])
            ->where('hijri_month', $data['hijri_month'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'hijri_month' => 'This month is already paid for this student.',
            ]);
        }

        try {
            $payment = StudentFeePayment::create([
                'student_id' => $student->id,
                'class_id' => $student->class_id,
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
                'hijri_month' => 'This month is already paid for this student.',
            ]);
        }

        return response()->json($payment->load(['student.schoolClass', 'account']), 201);
    }

    public function destroy(StudentFeePayment $fee)
    {
        $fee->delete();

        return response()->json(['message' => 'Fee payment removed.']);
    }
}
