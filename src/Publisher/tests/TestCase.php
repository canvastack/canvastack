<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Database\Seeders\TestDatabaseSeeder;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    
    /**
     * Indicates whether test database should be seeded.
     * 
     * Set to true in test classes that need test data.
     * 
     * @var bool
     */
    protected $seedTestDatabase = false;
    
    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Automatically seed test database if requested
        if ($this->seedTestDatabase) {
            $this->seedTestData();
        }
    }
    
    /**
     * Seed test database with test data.
     * 
     * Uses artisan command to ensure data persists across test isolation boundaries.
     * 
     * @return void
     */
    protected function seedTestData(): void
    {
        // Only seed once per test run to improve performance
        static $seeded = false;
        
        if (!$seeded) {
            // Use artisan command instead of $this->seed() for better persistence
            \Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\TestDatabaseSeeder',
                '--force' => true
            ]);
            
            // Verify seeding was successful
            $moduleCount = \Canvastack\Canvastack\Models\Admin\System\Modules::where('id', '>=', 1000)
                ->where('id', '<', 2000)
                ->count();
            
            if ($moduleCount > 0) {
                echo "\n✓ Test database seeded successfully ({$moduleCount} modules)\n";
            } else {
                echo "\n⚠ Warning: Test database seeding may have failed (0 modules found)\n";
            }
            
            $seeded = true;
        }
    }
}
