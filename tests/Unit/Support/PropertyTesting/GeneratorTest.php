<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\PropertyTesting;

use Canvastack\Canvastack\Tests\Support\PropertyTesting\Generator;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for Generator class.
 *
 * Verifies that all generators produce valid data matching the test schema.
 */
class GeneratorTest extends TestCase
{
    /**
     * Test that userFields generator produces valid field names.
     *
     * @return void
     */
    public function test_user_fields_generator_produces_valid_fields(): void
    {
        $validFields = ['id', 'name', 'email', 'password', 'is_super_admin', 'active', 'created_at', 'updated_at'];
        
        $generator = Generator::userFields();
        
        for ($i = 0; $i < 10; $i++) {
            $generator->next();
            $field = $generator->current();
            
            $this->assertContains(
                $field,
                $validFields,
                "Generated field '{$field}' should be a valid user field"
            );
        }
    }

    /**
     * Test that postFields generator produces valid field names.
     *
     * @return void
     */
    public function test_post_fields_generator_produces_valid_fields(): void
    {
        $validFields = ['id', 'title', 'content', 'user_id', 'status', 'featured', 'excerpt', 'metadata', 'created_at', 'updated_at'];
        
        $generator = Generator::postFields();
        
        for ($i = 0; $i < 10; $i++) {
            $generator->next();
            $field = $generator->current();
            
            $this->assertContains(
                $field,
                $validFields,
                "Generated field '{$field}' should be a valid post field"
            );
        }
    }

    /**
     * Test that postStatus generator produces valid status values.
     *
     * @return void
     */
    public function test_post_status_generator_produces_valid_statuses(): void
    {
        $validStatuses = ['draft', 'published', 'archived'];
        
        $generator = Generator::postStatus();
        
        for ($i = 0; $i < 10; $i++) {
            $generator->next();
            $status = $generator->current();
            
            $this->assertContains(
                $status,
                $validStatuses,
                "Generated status '{$status}' should be a valid post status"
            );
        }
    }

    /**
     * Test that provinceFields generator produces valid field names.
     *
     * @return void
     */
    public function test_province_fields_generator_produces_valid_fields(): void
    {
        $validFields = ['id', 'name', 'code', 'created_at', 'updated_at'];
        
        $generator = Generator::provinceFields();
        
        for ($i = 0; $i < 10; $i++) {
            $generator->next();
            $field = $generator->current();
            
            $this->assertContains(
                $field,
                $validFields,
                "Generated field '{$field}' should be a valid province field"
            );
        }
    }

    /**
     * Test that cityFields generator produces valid field names.
     *
     * @return void
     */
    public function test_city_fields_generator_produces_valid_fields(): void
    {
        $validFields = ['id', 'province_id', 'name', 'code', 'created_at', 'updated_at'];
        
        $generator = Generator::cityFields();
        
        for ($i = 0; $i < 10; $i++) {
            $generator->next();
            $field = $generator->current();
            
            $this->assertContains(
                $field,
                $validFields,
                "Generated field '{$field}' should be a valid city field"
            );
        }
    }

    /**
     * Test that tableFields generator produces valid field arrays.
     *
     * @return void
     */
    public function test_table_fields_generator_produces_valid_field_arrays(): void
    {
        $validFields = ['id', 'name', 'email', 'active', 'created_at'];
        
        $generator = Generator::tableFields();
        
        for ($i = 0; $i < 10; $i++) {
            $generator->next();
            $fields = $generator->current();
            
            $this->assertIsArray($fields, 'Generated value should be an array');
            $this->assertNotEmpty($fields, 'Generated array should not be empty');
            
            foreach ($fields as $field) {
                $this->assertContains(
                    $field,
                    $validFields,
                    "Generated field '{$field}' should be a valid table field"
                );
            }
        }
    }

    /**
     * Test that sortableColumns generator produces valid column names.
     *
     * @return void
     */
    public function test_sortable_columns_generator_produces_valid_columns(): void
    {
        $validColumns = ['id', 'name', 'email', 'created_at', 'updated_at'];
        
        $generator = Generator::sortableColumns();
        
        for ($i = 0; $i < 10; $i++) {
            $generator->next();
            $column = $generator->current();
            
            $this->assertContains(
                $column,
                $validColumns,
                "Generated column '{$column}' should be a valid sortable column"
            );
        }
    }

    /**
     * Test that searchableColumns generator produces valid column names.
     *
     * @return void
     */
    public function test_searchable_columns_generator_produces_valid_columns(): void
    {
        $validColumns = ['name', 'email', 'title', 'content'];
        
        $generator = Generator::searchableColumns();
        
        for ($i = 0; $i < 10; $i++) {
            $generator->next();
            $column = $generator->current();
            
            $this->assertContains(
                $column,
                $validColumns,
                "Generated column '{$column}' should be a valid searchable column"
            );
        }
    }

    /**
     * Test that jsonPaths generator produces valid JSON paths.
     *
     * @return void
     */
    public function test_json_paths_generator_produces_valid_paths(): void
    {
        $validPaths = [
            'seo.title',
            'seo.description',
            'seo.keywords',
            'social.image',
            'social.title',
            'layout.sidebar',
            'layout.header',
        ];
        
        $generator = Generator::jsonPaths();
        
        for ($i = 0; $i < 10; $i++) {
            $generator->next();
            $path = $generator->current();
            
            $this->assertContains(
                $path,
                $validPaths,
                "Generated path '{$path}' should be a valid JSON path"
            );
        }
    }

    /**
     * Test that all generators produce consistent output.
     *
     * @return void
     */
    public function test_generators_produce_consistent_output(): void
    {
        $generators = [
            'userFields' => Generator::userFields(),
            'postFields' => Generator::postFields(),
            'postStatus' => Generator::postStatus(),
            'provinceFields' => Generator::provinceFields(),
            'cityFields' => Generator::cityFields(),
            'sortableColumns' => Generator::sortableColumns(),
            'searchableColumns' => Generator::searchableColumns(),
            'jsonPaths' => Generator::jsonPaths(),
        ];
        
        foreach ($generators as $name => $generator) {
            $values = [];
            
            for ($i = 0; $i < 100; $i++) {
                $generator->next();
                $value = $generator->current();
                
                // Convert arrays to strings for comparison
                if (is_array($value)) {
                    $values[] = json_encode($value);
                } else {
                    $values[] = $value;
                }
            }
            
            $this->assertCount(
                100,
                $values,
                "Generator '{$name}' should produce 100 values"
            );
            
            $this->assertNotEmpty(
                array_unique($values),
                "Generator '{$name}' should produce varied values"
            );
        }
    }
}
