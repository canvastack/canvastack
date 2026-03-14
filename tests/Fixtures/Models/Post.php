<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Test fixture model for Post.
 */
class Post extends Model
{
    protected $table = 'posts';
    
    protected $fillable = [
        'title',
        'content',
        'user_id',
        'status',
    ];

    /**
     * Get the user that owns the post.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
