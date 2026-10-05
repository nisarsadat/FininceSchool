<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('school_name')->default('EduFinance Pro');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->decimal('monthly_fee', 12, 2);
            $table->text('details')->nullable();
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('father_name');
            $table->string('grandfather_name')->nullable();
            $table->string('id_card_number')->nullable()->unique();
            $table->foreignId('class_id')->constrained('school_classes')->restrictOnDelete();
            $table->text('details')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('father_name');
            $table->date('join_date');
            $table->decimal('monthly_salary', 12, 2);
            $table->text('details')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->text('details')->nullable();
            $table->timestamps();
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('student_fee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_id')->constrained('school_classes')->restrictOnDelete();
            $table->unsignedSmallInteger('hijri_year');
            $table->unsignedTinyInteger('hijri_month');
            $table->unsignedSmallInteger('receipt_year');
            $table->unsignedTinyInteger('receipt_month');
            $table->unsignedTinyInteger('receipt_day');
            $table->date('paid_on');
            $table->decimal('amount', 12, 2);
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'hijri_year', 'hijri_month']);
            $table->index(['hijri_year', 'hijri_month']);
        });

        Schema::create('teacher_salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('hijri_year');
            $table->unsignedTinyInteger('hijri_month');
            $table->unsignedSmallInteger('receipt_year');
            $table->unsignedTinyInteger('receipt_month');
            $table->unsignedTinyInteger('receipt_day');
            $table->date('paid_on');
            $table->decimal('amount', 12, 2);
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['teacher_id', 'hijri_year', 'hijri_month']);
            $table->index(['hijri_year', 'hijri_month']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->unsignedSmallInteger('hijri_year');
            $table->unsignedTinyInteger('hijri_month');
            $table->unsignedTinyInteger('hijri_day');
            $table->date('spent_on');
            $table->text('description');
            $table->timestamps();
            $table->index(['hijri_year', 'hijri_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('teacher_salary_payments');
        Schema::dropIfExists('student_fee_payments');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('students');
        Schema::dropIfExists('school_classes');
        Schema::dropIfExists('settings');
    }
};
