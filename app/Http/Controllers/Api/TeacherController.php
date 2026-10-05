<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Services\FinanceService;
use App\Support\SolarHijri;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $teachers = Teacher::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('father_name', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $teachers]);
    }

    public function store(Request $request)
    {
        $teacher = Teacher::create($this->validated($request));

        return response()->json($teacher, 201);
    }

    public function show(Teacher $teacher)
    {
        return response()->json($teacher);
    }

    public function profile(Request $request, Teacher $teacher, FinanceService $finance)
    {
        $year = $finance->resolveYear($request->integer('year') ?: null);

        return response()->json([
            'teacher' => $teacher,
            'year' => $year,
            'ledger' => $finance->teacherLedger($teacher, $year),
            'payments' => $teacher->salaryPayments()->with('account')->orderByDesc('hijri_year')->orderByDesc('hijri_month')->get(),
        ]);
    }

    public function update(Request $request, Teacher $teacher)
    {
        $teacher->update($this->validated($request));

        return response()->json($teacher);
    }

    public function destroy(Teacher $teacher)
    {
        if ($teacher->salaryPayments()->exists()) {
            throw ValidationException::withMessages([
                'teacher' => 'This teacher has salary payments and cannot be deleted.',
            ]);
        }

        $teacher->delete();

        return response()->json(['message' => 'Teacher deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'father_name' => ['required', 'string', 'max:255'],
            'hijri_year' => ['required', 'integer', 'between:1300,1500'],
            'hijri_month' => ['required', 'integer', 'between:1,12'],
            'hijri_day' => ['required', 'integer', 'between:1,31'],
            'monthly_salary' => ['required', 'numeric', 'min:0'],
            'details' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        return [
            'name' => $data['name'],
            'father_name' => $data['father_name'],
            'join_date' => SolarHijri::validatedGregorian($data['hijri_year'], $data['hijri_month'], $data['hijri_day']),
            'monthly_salary' => $data['monthly_salary'],
            'details' => blank($data['details'] ?? null) ? null : $data['details'],
            'status' => $data['status'],
        ];
    }
}
