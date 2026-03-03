<?php

declare(strict_types=1);

/**
 * Example: Enhanced displayRowsLimitOnLoad() with Session Persistence
 *
 * This example demonstrates the enhanced displayRowsLimitOnLoad() method
 * that now supports session persistence for better user experience.
 *
 * Requirements implemented:
 * - 3.1.1: Enhanced displayRowsLimitOnLoad() method
 *   - Support integer values
 *   - Support 'all' and '*' values
 *   - Implement validation
 *   - Implement session persistence
 */

use Canvastack\Canvastack\Components\Table\TableBuilder;
use App\Models\User;

class DisplayLimitSessionExample
{
    public function basicUsage(TableBuilder $table): void
    {
        // Set table model and enable session persistence
        $table->setModel(new User());
        $table->sessionFilters(); // This initializes the session manager
        
        // Set display limit - now automatically saved to session
        $table->displayRowsLimitOnLoad(25);
        
        // Configure table
        $table->setFields(['name:Name', 'email:Email', 'created_at:Created']);
        $table->format();
        
        // The limit of 25 is now saved to session and will be restored
        // on the next request for this table
    }
    
    public function showAllRows(TableBuilder $table): void
    {
        $table->setModel(new User());
        $table->sessionFilters();
        
        // Show all rows - also saved to session
        $table->displayRowsLimitOnLoad('all');
        // or alternatively:
        // $table->displayRowsLimitOnLoad('*');
        
        $table->setFields(['name:Name', 'email:Email']);
        $table->format();
    }
    
    public function sessionPersistenceDemo(TableBuilder $table): void
    {
        $table->setModel(new User());
        $table->sessionFilters();
        
        // First request: Set limit to 50
        $table->displayRowsLimitOnLoad(50);
        
        // On subsequent requests, the limit will be automatically
        // restored from session when sessionFilters() is called
        
        // You can check the current limit (from session or property)
        $currentLimit = $table->getDisplayLimit();
        echo "Current display limit: " . $currentLimit; // Will be 50
        
        $table->setFields(['name:Name', 'email:Email']);
        $table->format();
    }
    
    public function withoutSessionPersistence(TableBuilder $table): void
    {
        $table->setModel(new User());
        
        // Without calling sessionFilters(), no session persistence
        $table->displayRowsLimitOnLoad(30);
        
        // This limit will not be saved to session
        // and will reset to default on next request
        
        $table->setFields(['name:Name', 'email:Email']);
        $table->format();
    }
    
    public function validationExamples(TableBuilder $table): void
    {
        $table->setModel(new User());
        $table->sessionFilters();
        
        // Valid integer values
        $table->displayRowsLimitOnLoad(10);   // ✅ Valid
        $table->displayRowsLimitOnLoad(25);   // ✅ Valid
        $table->displayRowsLimitOnLoad(100);  // ✅ Valid
        
        // Valid string values
        $table->displayRowsLimitOnLoad('all'); // ✅ Valid
        $table->displayRowsLimitOnLoad('*');   // ✅ Valid (converted to 'all')
        
        // Invalid values (will throw InvalidArgumentException)
        try {
            $table->displayRowsLimitOnLoad(0);        // ❌ Invalid: zero
        } catch (\InvalidArgumentException $e) {
            echo "Error: " . $e->getMessage();
        }
        
        try {
            $table->displayRowsLimitOnLoad(-10);      // ❌ Invalid: negative
        } catch (\InvalidArgumentException $e) {
            echo "Error: " . $e->getMessage();
        }
        
        try {
            $table->displayRowsLimitOnLoad('invalid'); // ❌ Invalid: string
        } catch (\InvalidArgumentException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
    
    public function controllerExample(): void
    {
        // Example usage in a Laravel controller
        
        // public function index(TableBuilder $table): View
        // {
        //     $table->setModel(new User());
        //     $table->sessionFilters(); // Enable session persistence
        //     
        //     // Set default limit (will be overridden by session if exists)
        //     $table->displayRowsLimitOnLoad(25);
        //     
        //     $table->setFields([
        //         'name:Name',
        //         'email:Email',
        //         'created_at:Created'
        //     ]);
        //     
        //     $table->format();
        //     
        //     return view('users.index', ['table' => $table]);
        // }
    }
}