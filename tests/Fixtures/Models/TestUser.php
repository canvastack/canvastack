<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * TestUser Model - For testing purposes only.
 */
class TestUser extends Model implements Authenticatable
{
    protected $table = 'test_users';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Get the name of the unique identifier for the user.
     */
    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    /**
     * Get the unique identifier for the user.
     */
    public function getAuthIdentifier(): mixed
    {
        return $this->getAttribute('id');
    }

    /**
     * Get the password for the user.
     */
    public function getAuthPassword(): string
    {
        return $this->getAttribute('password');
    }

    /**
     * Get the column name for the password.
     */
    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    /**
     * Get the token value for the "remember me" session.
     */
    public function getRememberToken(): ?string
    {
        return $this->getAttribute('remember_token');
    }

    /**
     * Set the token value for the "remember me" session.
     */
    public function setRememberToken($value): void
    {
        $this->setAttribute('remember_token', $value);
    }

    /**
     * Get the column name for the "remember me" token.
     */
    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    /**
     * Example relationship for testing.
     */
    public function posts()
    {
        return $this->hasMany(TestPost::class, 'user_id');
    }

    /**
     * Example relationship for testing.
     */
    public function profile()
    {
        return $this->hasOne(TestProfile::class, 'user_id');
    }
}
