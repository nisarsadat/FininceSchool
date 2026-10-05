<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Services\FinanceService;
use App\Support\SolarHijri;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request, FinanceService $finance)
    {
        $year = $finance->resolveYear($request->integer('year') ?: null);

        $expenses = Expense::query()
            ->with(['category', 'account'])
            ->where('hijri_year', $year)
            ->when($request->filled('month'), fn ($query) => $query->where('hijri_month', $request->integer('month')))
            ->when($request->filled('expense_category_id'), fn ($query) => $query->where('expense_category_id', $request->integer('expense_category_id')))
            ->when($request->filled('account_id'), fn ($query) => $query->where('account_id', $request->integer('account_id')))
            ->when($request->filled('search'), fn ($query) => $query->where('description', 'like', '%'.$request->string('search')->trim().'%'))
            ->orderByDesc('hijri_month')
            ->orderByDesc('hijri_day')
            ->get();

        return response()->json(['data' => $expenses, 'year' => $year]);
    }

    public function store(Request $request)
    {
        $expense = Expense::create($this->payload($request));

        return response()->json($expense->load(['category', 'account']), 201);
    }

    public function show(Expense $expense)
    {
        return response()->json($expense->load(['category', 'account']));
    }

    public function update(Request $request, Expense $expense)
    {
        $expense->update($this->payload($request));

        return response()->json($expense->load(['category', 'account']));
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return response()->json(['message' => 'Expense deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        $data = $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'account_id' => ['required', 'exists:accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'hijri_year' => ['required', 'integer', 'between:1300,1500'],
            'hijri_month' => ['required', 'integer', 'between:1,12'],
            'hijri_day' => ['required', 'integer', 'between:1,31'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        return [
            'expense_category_id' => $data['expense_category_id'],
            'account_id' => $data['account_id'],
            'amount' => $data['amount'],
            'hijri_year' => $data['hijri_year'],
            'hijri_month' => $data['hijri_month'],
            'hijri_day' => $data['hijri_day'],
            'spent_on' => SolarHijri::validatedGregorian($data['hijri_year'], $data['hijri_month'], $data['hijri_day']),
            'description' => $data['description'],
        ];
    }
}
