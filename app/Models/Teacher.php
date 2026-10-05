<?php

namespace App\Models;

use App\Support\SolarHijri;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    protected $fillable = [
        'name',
        'father_name',
        'join_date',
        'monthly_salary',
        'details',
        'status',
    ];

    protected $appends = [
        'join_hijri',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'monthly_salary' => 'decimal:2',
        ];
    }

    public function salaryPayments(): HasMany
    {
        return $this->hasMany(TeacherSalaryPayment::class);
    }

    /**
     * @return array{year: int, month: int, day: int, month_name: string, formatted: string}|null
     */
    public function getJoinHijriAttribute(): ?array
    {
        if (! $this->join_date) {
            return null;
        }

        return SolarHijri::fromGregorian(
            (int) $this->join_date->format('Y'),
            (int) $this->join_date->format('n'),
            (int) $this->join_date->format('j'),
        );
    }
}
