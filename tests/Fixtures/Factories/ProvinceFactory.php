<?php

namespace Canvastack\Canvastack\Tests\Fixtures\Factories;

use Canvastack\Canvastack\Tests\Fixtures\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProvinceFactory extends Factory
{
    protected $model = Province::class;

    public function definition(): array
    {
        static $counter = 0;
        $counter++;
        
        return [
            'name' => 'Province ' . $counter,
            'code' => strtoupper(substr(md5((string) $counter), 0, 2)),
        ];
    }
}
