<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up()
    {
        $exists = DB::table('admin_settings')->where('key', 'admin_password_hash')->exists();
        if ($exists) {
            return;
        }

        // Generate a one-time password at install time.
        // We intentionally do NOT commit any default credentials to source control.
        $oneTimePassword = Str::random(24);
        DB::table('admin_settings')->insert([
            'key' => 'admin_password_hash',
            'value' => Hash::make($oneTimePassword),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Print once to the console when migrations run.
        // The password is never stored in code or database plaintext.
        if (PHP_SAPI === 'cli') {
            fwrite(STDOUT, PHP_EOL . 'Admin one-time password: ' . $oneTimePassword . PHP_EOL);
            fwrite(STDOUT, 'Log in at /admin/login and change it immediately at /admin/password' . PHP_EOL . PHP_EOL);
        }
    }

    public function down()
    {
        DB::table('admin_settings')->where('key', 'admin_password_hash')->delete();
    }
};

