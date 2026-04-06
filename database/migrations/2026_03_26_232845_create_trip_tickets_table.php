<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trip_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('trip_no')->unique();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->foreignId('truck_id')->constrained('trucks')->cascadeOnDelete();
            $table->date('date_issued')->nullable();
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->dateTime('departure_time')->nullable();
            $table->dateTime('arrival_time')->nullable();
            $table->integer('distance_km')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->enum('status', ['Draft', 'In-Transit', 'Completed', 'Cancelled'])->default('Draft');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trip_tickets');
    }
};
