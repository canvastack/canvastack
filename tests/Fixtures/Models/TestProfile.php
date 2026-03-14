<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TestProfile Model - For testing relationships.
 */
class TestProfile extends Model
{
    protected $table = 'test_profiles';

    protected $fillable = [
        'user_id',
        'bio',
        'avatar',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user()
    {
        return $this->belongsTo(TestUser::class, 'user_id');
    }
}
