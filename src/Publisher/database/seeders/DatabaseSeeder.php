<?php

namespace Database\Seeders;

use Canvastack\Canvastack\Database\Seeders\IncodiyTableSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(IncodiyTableSeeder::class);
    }
}
