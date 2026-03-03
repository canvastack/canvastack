<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form;

use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use ReflectionClass;

/**
 * Test for FormBuilder permission integration.
 *
 * Note: These tests use reflection to test the permission property
 * without instantiating FormBuilder due to dependency issues in test environment.
 */
class FormBuilderPermissionTest extends TestCase
{
    /**
     * Test that permission property exists.
     */
    public function test_permission_property_exists(): void
    {
        $reflection = new ReflectionClass(FormBuilder::class);

        $this->assertTrue(
            $reflection->hasProperty('permission'),
            'FormBuilder should have a permission property'
        );

        $property = $reflection->getProperty('permission');
        $this->assertTrue(
            $property->isProtected(),
            'Permission property should be protected'
        );
    }

    /**
     * Test that setPermission method exists.
     */
    public function test_set_permission_method_exists(): void
    {
        $reflection = new ReflectionClass(FormBuilder::class);

        $this->assertTrue(
            $reflection->hasMethod('setPermission'),
            'FormBuilder should have setPermission method'
        );

        $method = $reflection->getMethod('setPermission');
        $this->assertTrue(
            $method->isPublic(),
            'setPermission method should be public'
        );

        $this->assertEquals(
            1,
            $method->getNumberOfParameters(),
            'setPermission should accept 1 parameter'
        );
    }

    /**
     * Test that getPermission method exists.
     */
    public function test_get_permission_method_exists(): void
    {
        $reflection = new ReflectionClass(FormBuilder::class);

        $this->assertTrue(
            $reflection->hasMethod('getPermission'),
            'FormBuilder should have getPermission method'
        );

        $method = $reflection->getMethod('getPermission');
        $this->assertTrue(
            $method->isPublic(),
            'getPermission method should be public'
        );

        $this->assertEquals(
            0,
            $method->getNumberOfParameters(),
            'getPermission should accept no parameters'
        );
    }

    /**
     * Test that setPermission returns self for fluent interface.
     */
    public function test_set_permission_return_type(): void
    {
        $reflection = new ReflectionClass(FormBuilder::class);
        $method = $reflection->getMethod('setPermission');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType, 'setPermission should have a return type');
        $this->assertEquals('self', $returnType->getName(), 'setPermission should return self');
    }

    /**
     * Test that getPermission returns nullable string.
     */
    public function test_get_permission_return_type(): void
    {
        $reflection = new ReflectionClass(FormBuilder::class);
        $method = $reflection->getMethod('getPermission');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType, 'getPermission should have a return type');
        $this->assertTrue($returnType->allowsNull(), 'getPermission should allow null');
    }

    /**
     * Test that setPermission parameter accepts nullable string.
     */
    public function test_set_permission_parameter_type(): void
    {
        $reflection = new ReflectionClass(FormBuilder::class);
        $method = $reflection->getMethod('setPermission');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters, 'setPermission should have 1 parameter');

        $param = $parameters[0];
        $this->assertEquals('permission', $param->getName(), 'Parameter should be named permission');
        $this->assertTrue($param->allowsNull(), 'Parameter should allow null');
    }

    /**
     * Test that permission property has correct default value.
     */
    public function test_permission_property_default_value(): void
    {
        $reflection = new ReflectionClass(FormBuilder::class);
        $property = $reflection->getProperty('permission');

        $this->assertTrue(
            $property->hasDefaultValue(),
            'Permission property should have a default value'
        );

        $this->assertNull(
            $property->getDefaultValue(),
            'Permission property default value should be null'
        );
    }
}
