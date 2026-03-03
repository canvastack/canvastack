<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Examples;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Display Limit UI Example
 * 
 * Demonstrates how to use the display limit UI component with TableBuilder.
 * Shows different configurations and integration patterns.
 */
class DisplayLimitUIExample
{
    /**
     * Example 1: Basic usage with default options
     */
    public function basicUsage(TableBuilder $table): View
    {
        // Configure table
        $table->setContext('admin');
        $table->setModel(new \App\Models\User());
        $table->setFields(['name:Name', 'email:Email', 'created_at:Created']);
        
        // Set initial display limit
        $table->displayRowsLimitOnLoad(25);
        
        // Enable session persistence
        $table->sessionFilters();
        
        $table->format();
        
        return view('examples.display-limit.basic', [
            'table' => $table,
            'displayLimitUI' => $table->renderDisplayLimitUI(),
        ]);
    }

    /**
     * Example 2: Custom options and styling
     */
    public function customOptions(TableBuilder $table): View
    {
        // Configure table
        $table->setContext('admin');
        $table->setModel(new \App\Models\User());
        $table->setFields(['name:Name', 'email:Email', 'created_at:Created']);
        
        // Set initial display limit
        $table->displayRowsLimitOnLoad(50);
        
        // Enable session persistence
        $table->sessionFilters();
        
        $table->format();
        
        // Custom options for display limit
        $customOptions = [
            ['value' => '5', 'label' => '5'],
            ['value' => '15', 'label' => '15'],
            ['value' => '30', 'label' => '30'],
            ['value' => '75', 'label' => '75'],
            ['value' => 'all', 'label' => 'Show All'],
        ];
        
        return view('examples.display-limit.custom', [
            'table' => $table,
            'displayLimitUI' => $table->renderDisplayLimitUI($customOptions, true, 'md'),
        ]);
    }

    /**
     * Example 3: Compact version without label
     */
    public function compactVersion(TableBuilder $table): View
    {
        // Configure table
        $table->setContext('admin');
        $table->setModel(new \App\Models\User());
        $table->setFields(['name:Name', 'email:Email', 'created_at:Created']);
        
        // Set initial display limit
        $table->displayRowsLimitOnLoad(10);
        
        // Enable session persistence
        $table->sessionFilters();
        
        $table->format();
        
        return view('examples.display-limit.compact', [
            'table' => $table,
            'displayLimitUI' => $table->renderDisplayLimitUI([], false, 'xs'),
        ]);
    }

    /**
     * Example 4: Using the Blade component directly
     */
    public function bladeComponent(): View
    {
        return view('examples.display-limit.blade-component');
    }

    /**
     * Example 5: Multiple tables with different limits
     */
    public function multipleTables(TableBuilder $table1, TableBuilder $table2): View
    {
        // Configure first table
        $table1->setContext('admin');
        $table1->setModel(new \App\Models\User());
        $table1->setFields(['name:Name', 'email:Email']);
        $table1->displayRowsLimitOnLoad(10);
        $table1->sessionFilters();
        $table1->format();
        
        // Configure second table
        $table2->setContext('admin');
        $table2->setModel(new \App\Models\Post());
        $table2->setFields(['title:Title', 'author:Author', 'created_at:Created']);
        $table2->displayRowsLimitOnLoad(25);
        $table2->sessionFilters();
        $table2->format();
        
        return view('examples.display-limit.multiple', [
            'usersTable' => $table1,
            'usersDisplayLimitUI' => $table1->renderDisplayLimitUI(),
            'postsTable' => $table2,
            'postsDisplayLimitUI' => $table2->renderDisplayLimitUI(),
        ]);
    }

    /**
     * Example 6: Integration with filters and tabs
     */
    public function withFiltersAndTabs(TableBuilder $table): View
    {
        // Configure table with tabs
        $table->setContext('admin');
        
        // Tab 1: Active users
        $table->openTab('Active Users');
        $table->setModel(new \App\Models\User());
        $table->setFields(['name:Name', 'email:Email', 'last_login:Last Login']);
        $table->displayRowsLimitOnLoad(20);
        $table->closeTab();
        
        // Tab 2: Inactive users
        $table->openTab('Inactive Users');
        $table->setModel(new \App\Models\User());
        $table->setFields(['name:Name', 'email:Email', 'created_at:Created']);
        $table->displayRowsLimitOnLoad(50);
        $table->closeTab();
        
        // Add filters
        $table->filterGroups('status', 'selectbox', true);
        $table->filterGroups('role', 'selectbox');
        
        // Enable session persistence
        $table->sessionFilters();
        
        $table->format();
        
        return view('examples.display-limit.with-filters-tabs', [
            'table' => $table,
            'displayLimitUI' => $table->renderDisplayLimitUI(),
        ]);
    }
}