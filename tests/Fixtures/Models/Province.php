<?php

namespace Canvastack\Canvastack\Tests\Fixtures\Models;

use Canvastack\Canvastack\Tests\Fixtures\Factories\ProvinceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;

    protected $table = 'test_provinces';

    protected $fillable = [
        'name',
        'code',
    ];

    public function cities()
    {
        return $this->hasMany(City::class);
    }

    protected static function newFactory()
    {
        return ProvinceFactory::new();
    }
}
