<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * User Model Fixture for Testing.
 */
class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'role',
        'status',
        'department_id',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
}
