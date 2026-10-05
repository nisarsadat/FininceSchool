<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentFeePayment;
use App\Models\Teacher;
use App\Models\TeacherSalaryPayment;
use App\Models\Role;
use App\Models\User;
use App\Support\Access;
use App\Support\SolarHijri;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Access::sync();
        $adminRole = Role::query()->where('slug', 'admin')->first();

        User::create([
            'name' => 'School Admin',
            'code' => 'ADMIN',
            'email' => 'admin@edufinance.pro',
            'password' => 'password',
            'role_id' => $adminRole?->id,
            'is_active' => true,
        ]);

        Setting::create([
            'school_name' => 'Ahmad Shah Baba High School',
            'address' => 'Kabul, Afghanistan',
            'phone' => '0700 000 000',
            'email' => 'office@edufinance.pro',
        ]);

        $classes = collect([
            ['name' => 'Grade 1', 'location' => 'Room 1', 'monthly_fee' => 500],
            ['name' => 'Grade 2', 'location' => 'Room 2', 'monthly_fee' => 700],
            ['name' => 'Grade 3', 'location' => 'Room 3', 'monthly_fee' => 800],
            ['name' => 'Grade 4', 'location' => 'Room 4', 'monthly_fee' => 900],
            ['name' => 'Grade 5', 'location' => 'Room 5', 'monthly_fee' => 1000],
            ['name' => 'Grade 6', 'location' => 'Room 6', 'monthly_fee' => 1500],
        ])->map(fn (array $row) => SchoolClass::create($row));

        $byName = $classes->keyBy('name');

        $students = [
            ['name' => 'Ahmad', 'father_name' => 'Karim', 'grandfather_name' => 'Mohammad', 'id_card_number' => '1400-1001', 'class' => 'Grade 5', 'status' => 'active'],
            ['name' => 'Zahra', 'father_name' => 'Hassan', 'grandfather_name' => 'Ali', 'id_card_number' => '1400-1002', 'class' => 'Grade 6', 'status' => 'active'],
            ['name' => 'Bilal', 'father_name' => 'Yusuf', 'grandfather_name' => 'Ibrahim', 'id_card_number' => '1400-1003', 'class' => 'Grade 4', 'status' => 'active'],
            ['name' => 'Mariam', 'father_name' => 'Omar', 'grandfather_name' => 'Hassan', 'id_card_number' => '1400-1004', 'class' => 'Grade 3', 'status' => 'active'],
            ['name' => 'Sami', 'father_name' => 'Rahim', 'grandfather_name' => 'Noor', 'id_card_number' => '1400-1005', 'class' => 'Grade 1', 'status' => 'active'],
            ['name' => 'Fatima', 'father_name' => 'Ahmad', 'grandfather_name' => 'Karim', 'id_card_number' => '1400-1006', 'class' => 'Grade 2', 'status' => 'active'],
            ['name' => 'Laila', 'father_name' => 'Karim', 'grandfather_name' => 'Mohammad', 'id_card_number' => '1400-1007', 'class' => 'Grade 6', 'status' => 'active'],
            ['name' => 'Nasir', 'father_name' => 'Gul', 'grandfather_name' => 'Ahmad', 'id_card_number' => '1400-1008', 'class' => 'Grade 5', 'status' => 'inactive'],
        ];

        $studentModels = collect($students)->mapWithKeys(function (array $row) use ($byName) {
            $student = Student::create([
                'name' => $row['name'],
                'father_name' => $row['father_name'],
                'grandfather_name' => $row['grandfather_name'],
                'id_card_number' => $row['id_card_number'],
                'class_id' => $byName[$row['class']]->id,
                'status' => $row['status'],
                'details' => null,
            ]);

            return [$row['name'] => $student];
        });

        $today = SolarHijri::today();
        $year = $today['year'];

        $teachers = collect([
            ['name' => 'Ahmad Shah', 'father_name' => 'Wali', 'salary' => 20000, 'joined' => [1403, 1, 15]],
            ['name' => 'Maryam Noori', 'father_name' => 'Jalil', 'salary' => 18000, 'joined' => [1404, 2, 3]],
            ['name' => 'Karimullah', 'father_name' => 'Daud', 'salary' => 22000, 'joined' => [1402, 6, 10]],
            ['name' => 'Spogmay', 'father_name' => 'Rahman', 'salary' => 16000, 'joined' => [1404, 7, 1]],
        ])->map(fn (array $row) => Teacher::create([
            'name' => $row['name'],
            'father_name' => $row['father_name'],
            'join_date' => SolarHijri::toGregorian($row['joined'][0], $row['joined'][1], $row['joined'][2]),
            'monthly_salary' => $row['salary'],
            'status' => 'active',
        ]))->keyBy('name');

        $cash = Account::create(['name' => 'Cash', 'type' => 'cash', 'opening_balance' => 0]);
        $bank = Account::create(['name' => 'Bank', 'type' => 'bank', 'opening_balance' => 0]);
        Account::create(['name' => 'Other', 'type' => 'other', 'opening_balance' => 0]);

        $categories = collect([
            'Electricity', 'Water', 'Rent', 'Internet', 'Supplies', 'Maintenance', 'Other',
        ])->mapWithKeys(fn (string $name) => [$name => ExpenseCategory::create(['name' => $name])]);

        $payFee = function (Student $student, int $month) use ($year, $cash) {
            if ($month > SolarHijri::today()['month'] && $year === SolarHijri::today()['year']) {
                return;
            }

            StudentFeePayment::create([
                'student_id' => $student->id,
                'class_id' => $student->class_id,
                'hijri_year' => $year,
                'hijri_month' => $month,
                'receipt_year' => $year,
                'receipt_month' => $month,
                'receipt_day' => 1,
                'paid_on' => SolarHijri::toGregorian($year, $month, 1),
                'amount' => $student->schoolClass->monthly_fee,
                'account_id' => $cash->id,
            ]);
        };

        foreach ([1, 2] as $month) {
            $payFee($studentModels['Ahmad'], $month);
            $payFee($studentModels['Laila'], $month);
        }

        for ($month = 1; $month <= $today['month']; $month++) {
            $payFee($studentModels['Bilal'], $month);
        }

        $payFee($studentModels['Sami'], 1);
        $payFee($studentModels['Fatima'], 1);

        $paySalary = function (Teacher $teacher, int $month, Account $account) use ($year) {
            TeacherSalaryPayment::create([
                'teacher_id' => $teacher->id,
                'hijri_year' => $year,
                'hijri_month' => $month,
                'receipt_year' => $year,
                'receipt_month' => $month,
                'receipt_day' => 1,
                'paid_on' => SolarHijri::toGregorian($year, $month, 1),
                'amount' => $teacher->monthly_salary,
                'account_id' => $account->id,
            ]);
        };

        if ($today['month'] >= 1) {
            $paySalary($teachers['Ahmad Shah'], 1, $cash);
        }
        if ($today['month'] >= 2) {
            $paySalary($teachers['Ahmad Shah'], 2, $cash);
        }
        $paySalary($teachers['Karimullah'], $today['month'], $bank);

        $spend = function (string $category, Account $account, float $amount, int $month, string $description) use ($year, $categories) {
            Expense::create([
                'expense_category_id' => $categories[$category]->id,
                'account_id' => $account->id,
                'amount' => $amount,
                'hijri_year' => $year,
                'hijri_month' => $month,
                'hijri_day' => 1,
                'spent_on' => SolarHijri::toGregorian($year, $month, 1),
                'description' => $description,
            ]);
        };

        $spend('Rent', $cash, 15000, 1, 'School building rent');
        if ($today['month'] >= 2) {
            $spend('Electricity', $cash, 2500, 2, 'Electricity bill');
        }
        if ($today['month'] >= 3) {
            $spend('Supplies', $cash, 800, 3, 'Classroom supplies');
        }
        $spend('Internet', $bank, 1200, $today['month'], 'Monthly internet');
        $spend('Water', $cash, 600, $today['month'], 'Water bill');
    }
}
