<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'school_name',
        'address',
        'phone',
        'email',
        'default_locale',
        'default_theme',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'school_name' => 'EduFinance Pro',
        ]);
    }
}
