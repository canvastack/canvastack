<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Canvastack\Origin\Database\Seeders\CanvastackTableSeeder;

class DatabaseSeeder extends Seeder {
	/**
	 * Seed the application's database.
	 *
	 * @return void
	 */
	public function run() {
		$this->call(CanvastackTableSeeder::class);
	}
}
