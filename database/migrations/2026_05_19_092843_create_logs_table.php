<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();

            // What happened
            $table->string('action');
            // e.g. 'created', 'updated', 'deleted', 'archived', 'restored',
            //      'status_changed', 'login', 'password_changed', 'compiled'

            // Which module/model was affected
            $table->string('subject_type')->nullable();
            // e.g. 'trip_ticket', 'driver', 'truck', 'maintenance_record',
            //      'report_compilation', 'admin_settings'

            $table->unsignedBigInteger('subject_id')->nullable();
            // The primary key of the affected row (null for non-row events like login)

            // Human-readable label for the affected record at the time of logging
            // (denormalized so the log stays readable even after deletion)
            $table->string('subject_label')->nullable();
            // e.g. trip_no, truck_code, driver full_name, etc.

            // Who performed the action
            // Keeping this simple (no users table yet); store a string identifier
            $table->string('performed_by')->default('admin');
            // Extend to a foreignId if you add a users table later

            // What changed (optional — store before/after for update events)
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Any extra context
            $table->text('notes')->nullable();

            // Request metadata (useful for auditing)
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();

            // Indexes for the most common queries
            $table->index(['subject_type', 'subject_id'], 'logs_subject_index');
            $table->index('action');
            $table->index('logged_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};