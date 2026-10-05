<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    protected $table = 'school_classes';

    protected $fillable = [
        'name',
        'location',
        'monthly_fee',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'monthly_fee' => 'decimal:2',
        ];
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function feePayments(): HasMany
    {
        return $this->hasMany(StudentFeePayment::class, 'class_id');
    }
}
