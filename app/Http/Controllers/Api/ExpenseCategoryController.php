<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::query()->withCount('expenses')->orderBy('name')->get();

        return response()->json(['data' => $categories]);
    }

    public function store(Request $request)
    {
        $category = ExpenseCategory::create($this->validated($request));

        return response()->json($category->loadCount('expenses'), 201);
    }

    public function show(ExpenseCategory $expenseCategory)
    {
        return response()->json($expenseCategory->loadCount('expenses'));
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $expenseCategory->update($this->validated($request, $expenseCategory));

        return response()->json($expenseCategory->loadCount('expenses'));
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        if ($expenseCategory->expenses()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'This category has expenses and cannot be deleted.',
            ]);
        }

        $expenseCategory->delete();

        return response()->json(['message' => 'Category deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?ExpenseCategory $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('expense_categories', 'name')->ignore($category?->id)],
        ]);
    }
}
