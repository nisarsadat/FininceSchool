<?php

namespace App\Models;

use App\Support\SolarHijri;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFeePayment extends Model
{
    protected $fillable = [
        'student_id',
        'class_id',
        'hijri_year',
        'hijri_month',
        'receipt_year',
        'receipt_month',
        'receipt_day',
        'paid_on',
        'amount',
        'account_id',
        'notes',
    ];

    protected $appends = [
        'hijri_label',
        'month_name',
        'receipt_label',
    ];

    protected function casts(): array
    {
        return [
            'paid_on' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function getHijriLabelAttribute(): string
    {
        return SolarHijri::monthName((int) $this->hijri_month).' '.$this->hijri_year;
    }

    public function getReceiptLabelAttribute(): string
    {
        return SolarHijri::format((int) $this->receipt_year, (int) $this->receipt_month, (int) $this->receipt_day);
    }

    public function getMonthNameAttribute(): string
    {
        return SolarHijri::monthName((int) $this->hijri_month);
    }
}
