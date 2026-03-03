<?php

namespace Canvastack\Canvastack\Tests\Fixtures\Models;

use Canvastack\Canvastack\Tests\Fixtures\Factories\CityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $table = 'test_cities';

    protected $fillable = [
        'province_id',
        'name',
        'code',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    protected static function newFactory()
    {
        return CityFactory::new();
    }
}
