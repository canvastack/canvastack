<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TestPost Model - For testing relationships.
 */
class TestPost extends Model
{
    protected $table = 'test_posts';

    protected $fillable = [
        'user_id',
        'title',
        'content',
    ];

    /**
     * Get the user that owns the post.
     */
    public function user()
    {
        return $this->belongsTo(TestUser::class, 'user_id');
    }
}
