<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Left over from the admin theme. The academy has no "customers" table, so on a fresh
 * database this migration does nothing (it used to stop `php artisan migrate` with
 * "no such table: customers").
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->string('other_phone')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->date('date_of_entry')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('company_name')->nullable();
            $table->string('country')->nullable();
            $table->string('service')->nullable();
            $table->string('heard_us')->nullable();
            $table->string('portal')->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'other_phone', 'whatsapp_number', 'date_of_entry', 'date_of_birth',
                'company_name', 'country', 'service', 'heard_us', 'portal'
            ]);
        });
    }
};
