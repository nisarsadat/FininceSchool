<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ExpenseCategory;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Support\SolarHijri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_salary_is_an_expense_and_duplicate_months_are_rejected(): void
    {
        $user = User::factory()->create();
        $today = SolarHijri::today();
        $account = Account::create(['name' => 'Cash', 'type' => 'cash', 'opening_balance' => 0]);
        $class = SchoolClass::create(['name' => 'Grade 5', 'location' => 'Room 5', 'monthly_fee' => 1000]);
        $student = Student::create([
            'name' => 'Ahmad',
            'father_name' => 'Karim',
            'class_id' => $class->id,
            'status' => 'active',
        ]);
        $teacher = Teacher::create([
            'name' => 'Ahmad Shah',
            'father_name' => 'Wali',
            'join_date' => SolarHijri::toGregorian($today['year'], 1, 1),
            'monthly_salary' => 20000,
            'status' => 'active',
        ]);
        $category = ExpenseCategory::create(['name' => 'Rent']);

        $fee = [
            'student_id' => $student->id,
            'hijri_year' => $today['year'],
            'hijri_month' => $today['month'],
            'receipt_year' => $today['year'],
            'receipt_month' => $today['month'],
            'receipt_day' => 1,
            'amount' => 1000,
            'account_id' => $account->id,
        ];

        $this->actingAs($user)->postJson('/api/fees', $fee)->assertCreated();
        $this->actingAs($user)->postJson('/api/fees', $fee)->assertStatus(422);

        $this->actingAs($user)->postJson('/api/salaries', [
            'teacher_id' => $teacher->id,
            'hijri_year' => $today['year'],
            'hijri_month' => $today['month'],
            'receipt_year' => $today['year'],
            'receipt_month' => $today['month'],
            'receipt_day' => 1,
            'amount' => 20000,
            'account_id' => $account->id,
        ])->assertCreated();

        $this->actingAs($user)->postJson('/api/expenses', [
            'expense_category_id' => $category->id,
            'account_id' => $account->id,
            'amount' => 500,
            'hijri_year' => $today['year'],
            'hijri_month' => $today['month'],
            'hijri_day' => 1,
            'description' => 'Building rent share',
        ])->assertCreated();

        $dashboard = $this->actingAs($user)->getJson('/api/dashboard?year='.$today['year'])->assertOk()->json();

        $this->assertSame(1000.0, (float) $dashboard['income_this_month']);
        $this->assertSame(20500.0, (float) $dashboard['expenses_this_month']);
        $this->assertSame(-19500.0, (float) $dashboard['current_balance']);
        $this->assertSame(20500.0, (float) $dashboard['total_expenses']);

        $month = collect($dashboard['chart'])->firstWhere('month', $today['month']);
        $this->assertSame(1000.0, (float) $month['income']);
        $this->assertSame(20000.0, (float) $month['teacher_salaries']);
        $this->assertSame(500.0, (float) $month['other_expenses']);
        $this->assertSame(-19500.0, (float) $month['balance']);

        $reports = $this->actingAs($user)->getJson('/api/reports?year='.$today['year'])->assertOk()->json();
        $this->assertSame(1000.0, (float) $reports['income']['total']);
        $this->assertSame('Grade 5', $reports['income']['by_class'][0]['class']);
        $this->assertSame(20000.0, (float) $reports['salaries']['total_paid']);
    }
}
