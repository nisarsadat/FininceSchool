<?php

namespace App\Models;

use App\Support\SolarHijri;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSalaryPayment extends Model
{
    protected $fillable = [
        'teacher_id',
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

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
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
