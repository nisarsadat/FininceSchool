<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function index(FinanceService $finance)
    {
        return response()->json(['data' => $finance->accountSummaries()]);
    }

    public function store(Request $request)
    {
        $account = Account::create($this->validated($request));

        return response()->json($account, 201);
    }

    public function show(Account $account, FinanceService $finance)
    {
        $summary = collect($finance->accountSummaries())->firstWhere('id', $account->id);

        return response()->json($summary);
    }

    public function update(Request $request, Account $account)
    {
        $account->update($this->validated($request));

        return response()->json($account);
    }

    public function destroy(Account $account)
    {
        if ($account->feePayments()->exists() || $account->salaryPayments()->exists() || $account->expenses()->exists()) {
            throw ValidationException::withMessages([
                'account' => 'This account has payments or expenses and cannot be deleted.',
            ]);
        }

        $account->delete();

        return response()->json(['message' => 'Account deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['cash', 'bank', 'other'])],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['details'] = blank($data['details'] ?? null) ? null : $data['details'];

        return $data;
    }
}
