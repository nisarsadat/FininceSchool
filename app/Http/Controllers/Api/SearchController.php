<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Expense;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 1) {
            return response()->json([
                'students' => [],
                'teachers' => [],
                'classes' => [],
                'expenses' => [],
                'accounts' => [],
                'users' => [],
            ]);
        }

        $like = '%'.addcslashes($term, '%_\\').'%';
        $user = $request->user();

        return response()->json([
            'students' => $user->allows('students.view') ? $this->students($like) : [],
            'teachers' => $user->allows('teachers.view') ? $this->teachers($like) : [],
            'classes' => $user->allows('classes.view') ? $this->classes($like) : [],
            'expenses' => $user->allows('expenses.view') ? $this->expenses($like) : [],
            'accounts' => $user->allows('accounts.view') ? $this->accounts($like) : [],
            'users' => $user->allows('users.manage') ? $this->users($like) : [],
        ]);
    }

    private function students(string $like): array
    {
        return Student::query()
            ->with('schoolClass:id,name')
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('father_name', 'like', $like)
                    ->orWhere('grandfather_name', 'like', $like)
                    ->orWhere('id_card_number', 'like', $like);
            })
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'title' => $student->name,
                'subtitle' => trim($student->father_name.' · '.($student->schoolClass->name ?? '')),
                'to' => '/students/'.$student->id,
            ])
            ->all();
    }

    private function teachers(string $like): array
    {
        return Teacher::query()
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('father_name', 'like', $like);
            })
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(fn (Teacher $teacher) => [
                'id' => $teacher->id,
                'title' => $teacher->name,
                'subtitle' => $teacher->father_name,
                'to' => '/teachers/'.$teacher->id,
            ])
            ->all();
    }

    private function classes(string $like): array
    {
        return SchoolClass::query()
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('location', 'like', $like);
            })
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(fn (SchoolClass $class) => [
                'id' => $class->id,
                'title' => $class->name,
                'subtitle' => $class->location,
                'to' => '/classes',
            ])
            ->all();
    }

    private function expenses(string $like): array
    {
        return Expense::query()
            ->with('category:id,name')
            ->where('description', 'like', $like)
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (Expense $expense) => [
                'id' => $expense->id,
                'title' => $expense->description,
                'subtitle' => $expense->category->name ?? '',
                'to' => '/expenses',
            ])
            ->all();
    }

    private function accounts(string $like): array
    {
        return Account::query()
            ->where('name', 'like', $like)
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(fn (Account $account) => [
                'id' => $account->id,
                'title' => $account->name,
                'subtitle' => $account->type,
                'to' => '/accounts',
            ])
            ->all();
    }

    private function users(string $like): array
    {
        return User::query()
            ->with('role:id,name,slug')
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like);
            })
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(fn (User $person) => [
                'id' => $person->id,
                'title' => $person->name,
                'subtitle' => $person->code,
                'to' => '/users',
            ])
            ->all();
    }
}
