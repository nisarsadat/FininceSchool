<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExpenseCategoryController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SchoolClassController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\StudentFeeController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\TeacherSalaryController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/calendar', CalendarController::class);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/search', SearchController::class);

    Route::middleware('permission:dashboard.view')->get('/dashboard', DashboardController::class);
    Route::middleware('permission:reports.view')->get('/reports', ReportController::class);

    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/settings', [SettingController::class, 'show']);
        Route::put('/settings', [SettingController::class, 'update']);
        Route::post('/settings/reset', [SettingController::class, 'reset']);
    });

    Route::middleware('permission:users.manage')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });

    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:users.manage,roles.manage');
    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.manage');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.manage');

    Route::get('/students/{student}/profile', [StudentController::class, 'profile'])->middleware('permission:students.view');
    Route::get('/teachers/{teacher}/profile', [TeacherController::class, 'profile'])->middleware('permission:teachers.view');

    Route::apiResource('classes', SchoolClassController::class)
        ->parameters(['classes' => 'schoolClass'])
        ->middlewareFor(['index', 'show'], 'permission:classes.view')
        ->middlewareFor(['store', 'update', 'destroy'], 'permission:classes.manage');

    Route::apiResource('students', StudentController::class)
        ->middlewareFor(['index', 'show'], 'permission:students.view')
        ->middlewareFor(['store', 'update', 'destroy'], 'permission:students.manage');

    Route::apiResource('teachers', TeacherController::class)
        ->middlewareFor(['index', 'show'], 'permission:teachers.view')
        ->middlewareFor(['store', 'update', 'destroy'], 'permission:teachers.manage');

    Route::apiResource('accounts', AccountController::class)
        ->middlewareFor(['index', 'show'], 'permission:accounts.view')
        ->middlewareFor(['store', 'update', 'destroy'], 'permission:accounts.manage');

    Route::apiResource('expense-categories', ExpenseCategoryController::class)
        ->middlewareFor(['index', 'show'], 'permission:expenses.view')
        ->middlewareFor(['store', 'update', 'destroy'], 'permission:expenses.manage');

    Route::apiResource('expenses', ExpenseController::class)
        ->middlewareFor(['index', 'show'], 'permission:expenses.view')
        ->middlewareFor(['store', 'update', 'destroy'], 'permission:expenses.manage');

    Route::middleware('permission:payments.view')->group(function () {
        Route::get('/fees', [StudentFeeController::class, 'index']);
        Route::get('/fees/ledger/{student}', [StudentFeeController::class, 'ledger']);
        Route::get('/salaries', [TeacherSalaryController::class, 'index']);
        Route::get('/salaries/ledger/{teacher}', [TeacherSalaryController::class, 'ledger']);
    });

    Route::middleware('permission:payments.manage')->group(function () {
        Route::post('/fees', [StudentFeeController::class, 'store']);
        Route::delete('/fees/{fee}', [StudentFeeController::class, 'destroy']);
        Route::post('/salaries', [TeacherSalaryController::class, 'store']);
        Route::delete('/salaries/{salary}', [TeacherSalaryController::class, 'destroy']);
    });
});
