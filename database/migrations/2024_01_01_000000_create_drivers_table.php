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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone_number');
            $table->string('license_number');
            $table->date('license_expiry_date');
            $table->text('address');
            $table->string('emergency_contact');
            $table->string('file_path')->nullable();
            $table->string('assigned_truck')->nullable();
            $table->enum('status', ['Available', 'On-Leave', 'Covering'])->default('Available');
            $table->boolean('is_archived')->default(false);
            $table->integer('total_trips')->default(0);
            $table->date('last_trip')->nullable();
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
        Schema::dropIfExists('drivers');
    }
};

