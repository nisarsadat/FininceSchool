<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    public function index(Request $request, FinanceService $finance)
    {
        $year = $finance->resolveYear($request->integer('year') ?: null);
        $students = Student::query()
            ->with('schoolClass')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('father_name', 'like', "%{$search}%")
                        ->orWhere('grandfather_name', 'like', "%{$search}%")
                        ->orWhere('id_card_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('class_id'), fn ($query) => $query->where('class_id', $request->integer('class_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('name')
            ->get();

        $outstanding = collect($finance->outstandingStudents($year))->keyBy('student_id');

        $data = $students->map(function (Student $student) use ($outstanding) {
            $row = $outstanding->get($student->id);

            return array_merge($student->toArray(), [
                'outstanding' => $row['total_outstanding'] ?? 0,
                'unpaid_count' => $row['unpaid_count'] ?? 0,
                'unpaid_months' => $row['unpaid_months'] ?? [],
            ]);
        })->values();

        return response()->json(['data' => $data, 'year' => $year]);
    }

    public function store(Request $request)
    {
        $student = Student::create($this->validated($request));

        return response()->json($student->load('schoolClass'), 201);
    }

    public function show(Student $student)
    {
        return response()->json($student->load('schoolClass'));
    }

    public function profile(Request $request, Student $student, FinanceService $finance)
    {
        $year = $finance->resolveYear($request->integer('year') ?: null);
        $student->load('schoolClass');

        return response()->json([
            'student' => $student,
            'year' => $year,
            'ledger' => $finance->studentLedger($student, $year),
            'payments' => $student->feePayments()->with(['account', 'schoolClass'])->orderByDesc('hijri_year')->orderByDesc('hijri_month')->get(),
        ]);
    }

    public function update(Request $request, Student $student)
    {
        $student->update($this->validated($request, $student));

        return response()->json($student->load('schoolClass'));
    }

    public function destroy(Student $student)
    {
        if ($student->feePayments()->exists()) {
            throw ValidationException::withMessages([
                'student' => 'This student has fee payments and cannot be deleted.',
            ]);
        }

        $student->delete();

        return response()->json(['message' => 'Student deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Student $student = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'father_name' => ['required', 'string', 'max:255'],
            'grandfather_name' => ['nullable', 'string', 'max:255'],
            'id_card_number' => ['nullable', 'string', 'max:50', Rule::unique('students', 'id_card_number')->ignore($student?->id)],
            'class_id' => ['required', 'exists:school_classes,id'],
            'details' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $data['id_card_number'] = blank($data['id_card_number'] ?? null) ? null : $data['id_card_number'];
        $data['grandfather_name'] = blank($data['grandfather_name'] ?? null) ? null : $data['grandfather_name'];
        $data['details'] = blank($data['details'] ?? null) ? null : $data['details'];

        return $data;
    }
}
