<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Features\Ajax\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Test model for EloquentSyncAdapter tests.
 */
class TestCity extends Model
{
    protected $table = 'test_cities';

    protected $guarded = [];

    public function province(): BelongsTo
    {
        return $this->belongsTo(TestProvince::class, 'province_id');
    }
}
