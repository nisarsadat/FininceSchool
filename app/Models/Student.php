<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'name',
        'father_name',
        'grandfather_name',
        'id_card_number',
        'class_id',
        'details',
        'status',
    ];

    protected $appends = [
        'monthly_fee',
    ];

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function feePayments(): HasMany
    {
        return $this->hasMany(StudentFeePayment::class);
    }

    public function getMonthlyFeeAttribute(): ?string
    {
        return $this->schoolClass?->monthly_fee;
    }
}
