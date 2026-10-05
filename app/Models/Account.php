<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'name',
        'type',
        'opening_balance',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
        ];
    }

    public function feePayments(): HasMany
    {
        return $this->hasMany(StudentFeePayment::class);
    }

    public function salaryPayments(): HasMany
    {
        return $this->hasMany(TeacherSalaryPayment::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
