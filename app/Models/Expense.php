<?php

namespace App\Models;

use App\Support\SolarHijri;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'expense_category_id',
        'account_id',
        'amount',
        'hijri_year',
        'hijri_month',
        'hijri_day',
        'spent_on',
        'description',
    ];

    protected $appends = [
        'hijri_label',
        'month_name',
    ];

    protected function casts(): array
    {
        return [
            'spent_on' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function getHijriLabelAttribute(): string
    {
        return SolarHijri::format((int) $this->hijri_year, (int) $this->hijri_month, (int) $this->hijri_day);
    }

    public function getMonthNameAttribute(): string
    {
        return SolarHijri::monthName((int) $this->hijri_month);
    }
}
