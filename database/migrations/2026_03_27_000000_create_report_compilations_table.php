<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('report_compilations', function (Blueprint $table) {
            $table->id();
            $table->string('label')->nullable();
            $table->integer('completed_trip_count')->default(0);
            $table->decimal('completed_trip_revenue', 14, 2)->default(0);
            $table->decimal('tax_rate', 6, 4)->default(0.1200);
            $table->decimal('trip_tax', 14, 2)->default(0);
            $table->decimal('driver_expenses', 14, 2)->default(0);
            $table->decimal('maintenance_cost', 14, 2)->default(0);
            $table->decimal('net_profit', 14, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamp('compiled_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('report_compilations');
    }
};

