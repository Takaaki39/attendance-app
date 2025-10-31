<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $param = [
            'name' => 'user',
            'email' => 'user@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password111')
        ];
        DB::table('users')->insert($param);
    }
}
