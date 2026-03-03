<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Features\Ajax\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Test model for EloquentSyncAdapter tests.
 */
class TestProvince extends Model
{
    protected $table = 'test_provinces';

    protected $guarded = [];

    public function cities(): HasMany
    {
        return $this->hasMany(TestCity::class, 'province_id');
    }
}
