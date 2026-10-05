<?php

use App\Support\Access;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('group');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('name');
            $table->boolean('is_active')->default(true)->after('password');
            $table->foreignId('role_id')->nullable()->after('is_active')->constrained()->nullOnDelete();
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->string('default_locale')->default('en')->after('email');
            $table->string('default_theme')->default('light')->after('default_locale');
        });

        Access::sync();

        $adminId = DB::table('roles')->where('slug', 'admin')->value('id');

        DB::table('users')->orderBy('id')->get()->each(function ($user) use ($adminId) {
            $local = strtoupper((string) strtok((string) $user->email, '@'));
            $code = $local !== '' ? $local : 'USER'.$user->id;
            $taken = DB::table('users')->where('code', $code)->where('id', '!=', $user->id)->exists();

            DB::table('users')->where('id', $user->id)->update([
                'code' => $taken ? $code.$user->id : $code,
                'role_id' => $adminId,
                'is_active' => true,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['code', 'is_active']);
        });

        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['default_locale', 'default_theme']);
        });
    }
};
