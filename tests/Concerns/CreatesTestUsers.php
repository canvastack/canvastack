<?php

namespace Canvastack\Canvastack\Tests\Concerns;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Trait for creating test users in tests.
 *
 * This trait provides helper methods for creating authenticated test users
 * with various roles and permissions.
 */
trait CreatesTestUsers
{
    /**
     * Create a test user.
     *
     * @param array $attributes Additional attributes for the user
     * @return \Illuminate\Foundation\Auth\User
     */
    protected function createTestUser(array $attributes = []): Authenticatable
    {
        return new class ($attributes) extends Authenticatable {
            protected $fillable = ['name', 'email', 'password', 'role', 'theme_preference'];

            public function __construct(array $attributes = [])
            {
                parent::__construct(array_merge([
                    'id' => 1,
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'password' => bcrypt('password'),
                    'role' => 'user',
                    'theme_preference' => null,
                ], $attributes));
            }

            public function getAuthIdentifierName()
            {
                return 'id';
            }

            public function getAuthIdentifier()
            {
                return $this->attributes['id'];
            }

            public function getAuthPassword()
            {
                return $this->attributes['password'];
            }

            public function getRememberToken()
            {
                return null;
            }

            public function setRememberToken($value)
            {
                //
            }

            public function getRememberTokenName()
            {
                return null;
            }

            /**
             * Set theme preference for the user.
             *
             * @param string $theme
             * @return void
             */
            public function setThemePreference(string $theme): void
            {
                $this->attributes['theme_preference'] = $theme;
            }

            /**
             * Get theme preference for the user.
             *
             * @return string|null
             */
            public function getThemePreference(): ?string
            {
                return $this->attributes['theme_preference'] ?? null;
            }
        };
    }

    /**
     * Create an admin user.
     *
     * @param array $attributes Additional attributes for the user
     * @return \Illuminate\Foundation\Auth\User
     */
    protected function createAdminUser(array $attributes = []): Authenticatable
    {
        return $this->createTestUser(array_merge([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ], $attributes));
    }

    /**
     * Create a guest user (not authenticated).
     *
     * @return \Illuminate\Foundation\Auth\User
     */
    protected function createGuestUser(): Authenticatable
    {
        return $this->createTestUser([
            'name' => 'Guest User',
            'email' => 'guest@example.com',
            'role' => 'guest',
        ]);
    }

    /**
     * Authenticate as a test user.
     *
     * @param array $attributes Additional attributes for the user
     * @return \Illuminate\Foundation\Auth\User
     */
    protected function actingAsTestUser(array $attributes = []): Authenticatable
    {
        $user = $this->createTestUser($attributes);
        $this->actingAs($user);

        return $user;
    }

    /**
     * Authenticate as an admin user.
     *
     * @param array $attributes Additional attributes for the user
     * @return \Illuminate\Foundation\Auth\User
     */
    protected function actingAsAdmin(array $attributes = []): Authenticatable
    {
        $user = $this->createAdminUser($attributes);
        $this->actingAs($user);

        return $user;
    }
}
