<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Student;
use App\Models\StudentFeePayment;
use App\Models\Teacher;
use App\Models\TeacherSalaryPayment;
use App\Support\SolarHijri;
use Illuminate\Database\Eloquent\Builder;

class FinanceService
{
    /**
     * Student fees are the only income.
     * Teacher salaries and other expenses are both expenses.
     *
     * @return array{income: float, teacher_salaries: float, other_expenses: float, expenses: float, balance: float}
     */
    public function totals(?int $year = null, ?int $month = null): array
    {
        $income = (float) $this->feeQuery($year, $month)->sum('amount');
        $salaries = (float) $this->salaryQuery($year, $month)->sum('amount');
        $other = (float) $this->expenseQuery($year, $month)->sum('amount');
        $expenses = $salaries + $other;

        return [
            'income' => round($income, 2),
            'teacher_salaries' => round($salaries, 2),
            'other_expenses' => round($other, 2),
            'expenses' => round($expenses, 2),
            'balance' => round($income - $expenses, 2),
        ];
    }

    public function resolveYear(?int $year): int
    {
        $current = SolarHijri::today()['year'];

        if ($year === null || $year < 1300 || $year > 1500) {
            return $current;
        }

        return $year;
    }

    /**
     * Months that are already due in the selected Solar Hijri year.
     *
     * @return list<int>
     */
    public function dueMonths(int $year): array
    {
        $today = SolarHijri::today();

        if ($year < $today['year']) {
            return range(1, 12);
        }

        if ($year > $today['year']) {
            return [];
        }

        return range(1, $today['month']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function monthlyBreakdown(int $year): array
    {
        $rows = [];

        for ($month = 1; $month <= 12; $month++) {
            $rows[] = array_merge([
                'month' => $month,
                'name' => SolarHijri::monthName($month),
            ], $this->totals($year, $month));
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(int $year): array
    {
        $today = SolarHijri::today();
        $allTime = $this->totals();
        $yearTotals = $this->totals($year);
        $focusMonth = $year === $today['year'] ? $today['month'] : null;
        $period = $focusMonth ? $this->totals($year, $focusMonth) : $yearTotals;

        return [
            'today' => $today,
            'year' => $year,
            'focus_month' => $focusMonth,
            'focus_month_name' => $focusMonth ? SolarHijri::monthName($focusMonth) : null,
            'period_scope' => $focusMonth ? 'month' : 'year',
            'income_this_month' => $period['income'],
            'expenses_this_month' => $period['expenses'],
            'current_balance' => $allTime['balance'],
            'total_expenses' => $allTime['expenses'],
            'total_income' => $allTime['income'],
            'year_income' => $yearTotals['income'],
            'year_expenses' => $yearTotals['expenses'],
            'chart' => $this->monthlyBreakdown($year),
            'top_unpaid' => array_slice($this->outstandingStudents($year), 0, 6),
            'recent_payments' => $this->recentPayments(),
            'recent_expenses' => $this->recentExpenses(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function reports(int $year): array
    {
        $months = $this->monthlyBreakdown($year);
        $salaryRows = $this->salaryReport($year);

        return [
            'year' => $year,
            'income' => [
                'monthly' => array_map(fn (array $row) => [
                    'month' => $row['month'],
                    'name' => $row['name'],
                    'income' => $row['income'],
                ], $months),
                'total' => round(array_sum(array_column($months, 'income')), 2),
                'by_class' => $this->incomeByClass($year),
            ],
            'salaries' => [
                'rows' => $salaryRows,
                'total_paid' => round(array_sum(array_column($salaryRows, 'amount_paid')), 2),
            ],
            'expenses' => $this->expenseReport($year),
            'monthly' => $months,
            'outstanding' => $this->outstandingStudents($year),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function outstandingStudents(int $year): array
    {
        $due = $this->dueMonths($year);
        $students = Student::query()->with('schoolClass')->orderBy('name')->get();
        $paid = StudentFeePayment::query()
            ->where('hijri_year', $year)
            ->get(['student_id', 'hijri_month'])
            ->groupBy('student_id');

        $rows = [];

        foreach ($students as $student) {
            $fee = (float) ($student->schoolClass->monthly_fee ?? 0);
            $paidMonths = ($paid[$student->id] ?? collect())->pluck('hijri_month')->map(fn ($month) => (int) $month)->all();
            $unpaid = array_values(array_diff($due, $paidMonths));

            if ($unpaid === []) {
                continue;
            }

            $rows[] = [
                'student_id' => $student->id,
                'student' => $student->name,
                'father_name' => $student->father_name,
                'status' => $student->status,
                'class' => $student->schoolClass?->name,
                'class_id' => $student->class_id,
                'monthly_fee' => $fee,
                'unpaid_months' => array_map(fn (int $month) => [
                    'month' => $month,
                    'name' => SolarHijri::monthName($month),
                ], $unpaid),
                'unpaid_count' => count($unpaid),
                'total_outstanding' => round($fee * count($unpaid), 2),
            ];
        }

        usort($rows, function (array $a, array $b) {
            return $b['total_outstanding'] <=> $a['total_outstanding']
                ?: strcmp($a['student'], $b['student']);
        });

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function studentLedger(Student $student, int $year): array
    {
        $student->loadMissing('schoolClass');
        $due = $this->dueMonths($year);
        $payments = $student->feePayments()->with('account')->where('hijri_year', $year)->get()->keyBy('hijri_month');
        $fee = (float) ($student->schoolClass->monthly_fee ?? 0);
        $months = [];
        $unpaidCount = 0;

        for ($month = 1; $month <= 12; $month++) {
            $payment = $payments->get($month);
            $status = $payment ? 'paid' : (in_array($month, $due, true) ? 'unpaid' : 'upcoming');

            if ($status === 'unpaid') {
                $unpaidCount++;
            }

            $months[] = [
                'month' => $month,
                'name' => SolarHijri::monthName($month),
                'status' => $status,
                'payment' => $payment,
            ];
        }

        return [
            'student' => $student,
            'year' => $year,
            'monthly_fee' => $fee,
            'class_name' => $student->schoolClass?->name,
            'months' => $months,
            'unpaid_count' => $unpaidCount,
            'total_outstanding' => round($fee * $unpaidCount, 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function teacherLedger(Teacher $teacher, int $year): array
    {
        $due = $this->dueMonths($year);
        $payments = $teacher->salaryPayments()->with('account')->where('hijri_year', $year)->get()->keyBy('hijri_month');
        $salary = (float) $teacher->monthly_salary;
        $months = [];
        $unpaidCount = 0;

        for ($month = 1; $month <= 12; $month++) {
            $payment = $payments->get($month);
            $status = $payment ? 'paid' : (in_array($month, $due, true) ? 'unpaid' : 'upcoming');

            if ($status === 'unpaid') {
                $unpaidCount++;
            }

            $months[] = [
                'month' => $month,
                'name' => SolarHijri::monthName($month),
                'status' => $status,
                'payment' => $payment,
            ];
        }

        return [
            'teacher' => $teacher,
            'year' => $year,
            'monthly_salary' => $salary,
            'months' => $months,
            'unpaid_count' => $unpaidCount,
            'total_outstanding' => round($salary * $unpaidCount, 2),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function accountSummaries(): array
    {
        $accounts = Account::query()->orderBy('name')->get();
        $fees = StudentFeePayment::query()->selectRaw('account_id, SUM(amount) as total')->groupBy('account_id')->pluck('total', 'account_id');
        $salaries = TeacherSalaryPayment::query()->selectRaw('account_id, SUM(amount) as total')->groupBy('account_id')->pluck('total', 'account_id');
        $expenses = Expense::query()->selectRaw('account_id, SUM(amount) as total')->groupBy('account_id')->pluck('total', 'account_id');

        return $accounts->map(function (Account $account) use ($fees, $salaries, $expenses) {
            $income = (float) ($fees[$account->id] ?? 0);
            $salaryOut = (float) ($salaries[$account->id] ?? 0);
            $otherOut = (float) ($expenses[$account->id] ?? 0);
            $opening = (float) $account->opening_balance;

            return array_merge($account->toArray(), [
                'income' => round($income, 2),
                'teacher_salaries' => round($salaryOut, 2),
                'other_expenses' => round($otherOut, 2),
                'balance' => round($opening + $income - $salaryOut - $otherOut, 2),
            ]);
        })->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function incomeByClass(int $year): array
    {
        return StudentFeePayment::query()
            ->with('schoolClass')
            ->where('hijri_year', $year)
            ->get()
            ->groupBy('class_id')
            ->map(function ($group) {
                return [
                    'class' => $group->first()->schoolClass?->name ?? 'Unknown class',
                    'total' => round((float) $group->sum('amount'), 2),
                    'payments' => $group->count(),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function salaryReport(int $year): array
    {
        $due = $this->dueMonths($year);
        $teachers = Teacher::query()->orderBy('name')->get();
        $payments = TeacherSalaryPayment::query()->with('account')->where('hijri_year', $year)->get()->groupBy('teacher_id');
        $rows = [];

        foreach ($teachers as $teacher) {
            $paid = ($payments[$teacher->id] ?? collect())->keyBy(fn ($payment) => (int) $payment->hijri_month);

            for ($month = 1; $month <= 12; $month++) {
                $payment = $paid->get($month);
                $rows[] = [
                    'teacher_id' => $teacher->id,
                    'teacher' => $teacher->name,
                    'month' => $month,
                    'month_name' => SolarHijri::monthName($month),
                    'salary' => (float) $teacher->monthly_salary,
                    'status' => $payment ? 'paid' : (in_array($month, $due, true) ? 'unpaid' : 'upcoming'),
                    'amount_paid' => $payment ? (float) $payment->amount : 0,
                    'account' => $payment?->account?->name,
                ];
            }
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function expenseReport(int $year): array
    {
        return Expense::query()
            ->with(['category', 'account'])
            ->where('hijri_year', $year)
            ->orderBy('hijri_month')
            ->orderBy('hijri_day')
            ->get()
            ->map(fn (Expense $expense) => [
                'id' => $expense->id,
                'category' => $expense->category?->name,
                'account' => $expense->account?->name,
                'amount' => (float) $expense->amount,
                'month' => (int) $expense->hijri_month,
                'month_name' => SolarHijri::monthName((int) $expense->hijri_month),
                'date' => $expense->hijri_label,
                'description' => $expense->description,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentPayments(): array
    {
        return StudentFeePayment::query()
            ->with(['student.schoolClass', 'account'])
            ->latest('paid_on')
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (StudentFeePayment $payment) => [
                'id' => $payment->id,
                'student_id' => $payment->student_id,
                'student' => $payment->student?->name,
                'class' => $payment->student?->schoolClass?->name,
                'month_name' => SolarHijri::monthName((int) $payment->hijri_month),
                'date' => $payment->receipt_label,
                'amount' => (float) $payment->amount,
                'account' => $payment->account?->name,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentExpenses(): array
    {
        $salaries = TeacherSalaryPayment::query()
            ->with(['teacher', 'account'])
            ->latest('paid_on')
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (TeacherSalaryPayment $payment) => [
                'id' => 'salary-'.$payment->id,
                'kind' => 'salary',
                'teacher_id' => $payment->teacher_id,
                'month_name' => SolarHijri::monthName((int) $payment->hijri_month),
                'title' => $payment->teacher?->name,
                'detail' => 'Teacher salary · '.SolarHijri::monthName((int) $payment->hijri_month),
                'date' => $payment->receipt_label,
                'sort' => $payment->paid_on->format('Y-m-d').'-'.$payment->id,
                'amount' => (float) $payment->amount,
                'account' => $payment->account?->name,
            ]);

        $expenses = Expense::query()
            ->with(['category', 'account'])
            ->latest('spent_on')
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (Expense $expense) => [
                'id' => 'expense-'.$expense->id,
                'kind' => 'expense',
                'title' => $expense->category?->name,
                'detail' => $expense->description,
                'date' => $expense->hijri_label,
                'sort' => $expense->spent_on->format('Y-m-d').'-'.$expense->id,
                'amount' => (float) $expense->amount,
                'account' => $expense->account?->name,
            ]);

        return $salaries->concat($expenses)->sortByDesc('sort')->take(6)->values()->map(function (array $row) {
            unset($row['sort']);

            return $row;
        })->all();
    }

    private function feeQuery(?int $year, ?int $month): Builder
    {
        return StudentFeePayment::query()
            ->when($year, fn (Builder $query) => $query->where('hijri_year', $year))
            ->when($month, fn (Builder $query) => $query->where('hijri_month', $month));
    }

    private function salaryQuery(?int $year, ?int $month): Builder
    {
        return TeacherSalaryPayment::query()
            ->when($year, fn (Builder $query) => $query->where('hijri_year', $year))
            ->when($month, fn (Builder $query) => $query->where('hijri_month', $month));
    }

    private function expenseQuery(?int $year, ?int $month): Builder
    {
        return Expense::query()
            ->when($year, fn (Builder $query) => $query->where('hijri_year', $year))
            ->when($month, fn (Builder $query) => $query->where('hijri_month', $month));
    }
}
