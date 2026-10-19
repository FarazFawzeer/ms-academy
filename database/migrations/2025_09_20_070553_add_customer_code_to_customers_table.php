<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Left over from the admin theme. Does nothing when there is no "customers" table
 * (a fresh academy database), so `php artisan migrate` can finish.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->string('customer_code', 50)->unique()->after('id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('customer_code');
        });
    }
};
