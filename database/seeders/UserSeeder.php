<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name'           => 'Andri Suryono',
                'email'          => 'andri.suryono@gmail.com',
                'password'       => Hash::make('password'),
                'remember_token' => Str::random(10),
            ],
            [
                'name'           => 'Lilis',
                'email'          => 'lilis@gmail.com',
                'password'       => Hash::make('password'),
                'remember_token' => Str::random(10),
            ],
        ]);

    }
}
