<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The app needs users.type (Super Admin / Admin / Front Desk) and users.image_path,
 * but no migration created them. Safe to run on a database that already has them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'type')) {
                $table->string('type', 30)->default('Front Desk')->after('password');
            }
            if (!Schema::hasColumn('users', 'image_path')) {
                $table->string('image_path')->nullable()->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'image_path')) {
                $table->dropColumn('image_path');
            }
            if (Schema::hasColumn('users', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
