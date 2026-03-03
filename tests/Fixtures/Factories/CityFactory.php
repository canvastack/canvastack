<?php

namespace Canvastack\Canvastack\Tests\Fixtures\Factories;

use Canvastack\Canvastack\Tests\Fixtures\Models\City;
use Canvastack\Canvastack\Tests\Fixtures\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        static $counter = 0;
        $counter++;
        
        return [
            'province_id' => Province::factory(),
            'name' => 'City ' . $counter,
            'code' => strtoupper(substr(md5((string) $counter), 0, 3)),
        ];
    }
}
