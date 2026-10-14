<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coaches', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('phone', 20)->nullable();
            $t->string('nic', 20)->nullable();
            $t->string('photo_path')->nullable();
            $t->decimal('daily_rate', 10, 2)->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('batches', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('schedule')->nullable();
            $t->foreignId('coach_id')->nullable()->constrained('coaches')->nullOnDelete();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('players', function (Blueprint $t) {
            $t->id();
            $t->string('player_no', 20)->nullable()->unique();
            $t->string('qr_token', 16)->unique();           // random, NOT the id (can't be guessed)
            $t->string('name');
            $t->date('dob');
            $t->string('phone', 20)->nullable();
            $t->string('guardian_name');
            $t->string('guardian_phone', 20);
            $t->string('photo_path')->nullable();
            $t->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $t->date('joined_at')->nullable();

            // Paid sessions left. +8 on payment, -1 on attendance. Negative = attended without paying.
            $t->smallInteger('sessions_balance')->default(0);
            $t->boolean('reminder_pending')->default(false);
            $t->timestamp('last_reminded_at')->nullable();

            $t->boolean('is_active')->default(true);
            $t->timestamps();

            $t->index(['is_active', 'reminder_pending']);
            $t->index(['is_active', 'sessions_balance']);
            $t->index('name');
            $t->index('guardian_phone');
        });

        Schema::create('attendances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('player_id')->constrained()->cascadeOnDelete();
            $t->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $t->date('attendance_date');
            $t->string('method', 10)->default('qr');         // qr | manual
            $t->unsignedBigInteger('marked_by')->nullable();
            $t->timestamp('created_at')->useCurrent();

            $t->unique(['player_id', 'attendance_date']);    // one session per player per day
            $t->index(['attendance_date', 'batch_id']);
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('player_id')->constrained()->cascadeOnDelete();
            $t->decimal('amount', 10, 2);
            $t->unsignedSmallInteger('sessions')->default(8);
            $t->string('method', 20)->default('cash');
            $t->string('note')->nullable();
            $t->timestamp('paid_at')->useCurrent();
            $t->unsignedBigInteger('received_by')->nullable();
            $t->timestamps();

            $t->index('paid_at');
        });

        Schema::create('coach_workdays', function (Blueprint $t) {
            $t->id();
            $t->foreignId('coach_id')->constrained()->cascadeOnDelete();
            $t->date('work_date');
            $t->decimal('amount', 10, 2);                    // rate on that day (history safe if rate changes)
            $t->timestamp('paid_at')->nullable();

            $t->unique(['coach_id', 'work_date']);
            $t->index('work_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_workdays');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('players');
        Schema::dropIfExists('batches');
        Schema::dropIfExists('coaches');
    }
};
