<?php

namespace Canvastack\Canvastack\Core\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base Test Case untuk Core\Craft Tests
 * 
 * Menyediakan setup dasar untuk semua unit dan integration tests
 * 
 * @author CanvaStack Dev Team
 * @created 2024-12-19
 * @version 1.0
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup test environment
        $this->setupTestEnvironment();
    }

    /**
     * Setup test environment
     *
     * @return void
     */
    protected function setupTestEnvironment(): void
    {
        // Setup autoloader if needed
        // Initialize any required dependencies
        // Mock external services if needed
    }

    /**
     * Clean up after test
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Clean up test data
        // Reset mocks
        
        parent::tearDown();
    }
}