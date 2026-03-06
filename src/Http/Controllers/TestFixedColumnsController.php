<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Http\Controllers;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Library\Components\MetaTags;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * Test controller for Fixed Columns feature (Phase 4)
 * 
 * This controller demonstrates the usage of fixed columns in TableBuilder.
 */
class TestFixedColumnsController extends Controller
{
    /**
     * Test 1: Basic fixed columns (left only)
     */
    public function testLeftFixed(TableBuilder $table, MetaTags $meta): View
    {
        $meta->title('Fixed Columns Test - Left Only')
            ->description('Testing fixed columns from left side');
        
        $table->setContext('admin');
        
        // Create test data with many columns
        $testData = [];
        for ($i = 1; $i <= 50; $i++) {
            $testData[] = [
                'id' => $i,
                'name' => 'User ' . $i,
                'email' => 'user' . $i . '@example.com',
                'phone' => '+1234567890' . $i,
                'address' => $i . ' Main Street',
                'city' => 'City ' . $i,
                'state' => 'State ' . $i,
                'country' => 'Country ' . $i,
                'zip' => '1000' . $i,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }
        
        $table->setData($testData);
        $table->setFields([
            'id:ID',
            'name:Name',
            'email:Email',
            'phone:Phone',
            'address:Address',
            'city:City',
            'state:State',
            'country:Country',
            'zip:ZIP Code',
            'created_at:Created At'
        ]);
        
        // Fix first 2 columns from left (ID and Name)
        $table->fixedColumns(2, null);
        
        $table->format();
        
        return view('canvastack::test.fixed-columns', [
            'table' => $table,
            'meta' => $meta,
            'testName' => 'Left Fixed (2 columns)',
            'description' => 'ID and Name columns are fixed on the left. Scroll horizontally to see other columns.'
        ]);
    }
    
    /**
     * Test 2: Fixed columns (right only)
     */
    public function testRightFixed(TableBuilder $table, MetaTags $meta): View
    {
        $meta->title('Fixed Columns Test - Right Only')
            ->description('Testing fixed columns from right side');
        
        $table->setContext('admin');
        
        // Create test data
        $testData = [];
        for ($i = 1; $i <= 50; $i++) {
            $testData[] = [
                'id' => $i,
                'name' => 'User ' . $i,
                'email' => 'user' . $i . '@example.com',
                'phone' => '+1234567890' . $i,
                'address' => $i . ' Main Street',
                'city' => 'City ' . $i,
                'state' => 'State ' . $i,
                'country' => 'Country ' . $i,
                'zip' => '1000' . $i,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }
        
        $table->setData($testData);
        $table->setFields([
            'id:ID',
            'name:Name',
            'email:Email',
            'phone:Phone',
            'address:Address',
            'city:City',
            'state:State',
            'country:Country',
            'zip:ZIP Code',
            'created_at:Created At'
        ]);
        
        // Fix last 1 column from right (Created At)
        $table->fixedColumns(null, 1);
        
        $table->format();
        
        return view('canvastack::test.fixed-columns', [
            'table' => $table,
            'meta' => $meta,
            'testName' => 'Right Fixed (1 column)',
            'description' => 'Created At column is fixed on the right. Scroll horizontally to see other columns.'
        ]);
    }
    
    /**
     * Test 3: Fixed columns (both sides)
     */
    public function testBothFixed(TableBuilder $table, MetaTags $meta): View
    {
        $meta->title('Fixed Columns Test - Both Sides')
            ->description('Testing fixed columns from both sides');
        
        $table->setContext('admin');
        
        // Create test data
        $testData = [];
        for ($i = 1; $i <= 50; $i++) {
            $testData[] = [
                'id' => $i,
                'name' => 'User ' . $i,
                'email' => 'user' . $i . '@example.com',
                'phone' => '+1234567890' . $i,
                'address' => $i . ' Main Street',
                'city' => 'City ' . $i,
                'state' => 'State ' . $i,
                'country' => 'Country ' . $i,
                'zip' => '1000' . $i,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }
        
        $table->setData($testData);
        $table->setFields([
            'id:ID',
            'name:Name',
            'email:Email',
            'phone:Phone',
            'address:Address',
            'city:City',
            'state:State',
            'country:Country',
            'zip:ZIP Code',
            'created_at:Created At'
        ]);
        
        // Fix 2 columns from left (ID, Name) and 1 from right (Created At)
        $table->fixedColumns(2, 1);
        
        $table->format();
        
        return view('canvastack::test.fixed-columns', [
            'table' => $table,
            'meta' => $meta,
            'testName' => 'Both Sides Fixed (2 left, 1 right)',
            'description' => 'ID and Name are fixed on left, Created At is fixed on right. Scroll horizontally to see middle columns.'
        ]);
    }
    
    /**
     * Test 4: No fixed columns (normal table)
     */
    public function testNoFixed(TableBuilder $table, MetaTags $meta): View
    {
        $meta->title('Fixed Columns Test - No Fixed')
            ->description('Testing normal table without fixed columns');
        
        $table->setContext('admin');
        
        // Create test data
        $testData = [];
        for ($i = 1; $i <= 50; $i++) {
            $testData[] = [
                'id' => $i,
                'name' => 'User ' . $i,
                'email' => 'user' . $i . '@example.com',
                'phone' => '+1234567890' . $i,
                'address' => $i . ' Main Street',
                'city' => 'City ' . $i,
                'state' => 'State ' . $i,
                'country' => 'Country ' . $i,
                'zip' => '1000' . $i,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }
        
        $table->setData($testData);
        $table->setFields([
            'id:ID',
            'name:Name',
            'email:Email',
            'phone:Phone',
            'address:Address',
            'city:City',
            'state:State',
            'country:Country',
            'zip:ZIP Code',
            'created_at:Created At'
        ]);
        
        // No fixed columns - normal responsive table
        // Don't call fixedColumns() at all
        
        $table->format();
        
        return view('canvastack::test.fixed-columns', [
            'table' => $table,
            'meta' => $meta,
            'testName' => 'No Fixed Columns (Normal)',
            'description' => 'Normal responsive table without fixed columns. Table will be responsive on mobile.'
        ]);
    }
}
