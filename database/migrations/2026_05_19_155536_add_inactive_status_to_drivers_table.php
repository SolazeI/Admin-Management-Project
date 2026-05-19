<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE drivers MODIFY COLUMN status ENUM('Available', 'On-Leave', 'Covering', 'Inactive') DEFAULT 'Available'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE drivers MODIFY COLUMN status ENUM('Available', 'On-Leave', 'Covering') DEFAULT 'Available'");
    }
};