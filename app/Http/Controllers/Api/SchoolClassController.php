<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SchoolClassController extends Controller
{
    public function index()
    {
        $classes = SchoolClass::query()
            ->withCount('students')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $classes]);
    }

    public function store(Request $request)
    {
        $class = SchoolClass::create($this->validated($request));

        return response()->json($class->loadCount('students'), 201);
    }

    public function show(SchoolClass $schoolClass)
    {
        return response()->json($schoolClass->loadCount('students'));
    }

    public function update(Request $request, SchoolClass $schoolClass)
    {
        $schoolClass->update($this->validated($request));

        return response()->json($schoolClass->loadCount('students'));
    }

    public function destroy(SchoolClass $schoolClass)
    {
        if ($schoolClass->students()->exists() || $schoolClass->feePayments()->exists()) {
            throw ValidationException::withMessages([
                'class' => 'This class is used by students or fee payments and cannot be deleted.',
            ]);
        }

        $schoolClass->delete();

        return response()->json(['message' => 'Class deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
