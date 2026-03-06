<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Engines;

use Canvastack\Canvastack\Components\Table\Engines\TableEngineInterface;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test for TableEngineInterface contract.
 *
 * This test verifies that the TableEngineInterface defines the correct
 * contract for all table rendering engines. It validates that all required
 * methods are present and have the correct signatures.
 *
 * @package Canvastack\Canvastack\Tests\Unit\Components\Table\Engines
 * @version 1.0.0
 *
 * Validates:
 * - Requirement 3.1: TableEngineInterface defines the contract for all engines
 * - Requirement 29.2: Unit tests for engine interface
 */
class TableEngineInterfaceTest extends TestCase
{
    /**
     * Test that TableEngineInterface exists.
     *
     * @return void
     */
    #[Test]
    public function test_interface_exists(): void
    {
        $this->assertTrue(
            interface_exists(TableEngineInterface::class),
            'TableEngineInterface should exist'
        );
    }

    /**
     * Test that TableEngineInterface has render() method.
     *
     * @return void
     */
    #[Test]
    public function test_interface_has_render_method(): void
    {
        $this->assertTrue(
            method_exists(TableEngineInterface::class, 'render'),
            'TableEngineInterface should have render() method'
        );

        $reflection = new \ReflectionMethod(TableEngineInterface::class, 'render');

        // Verify return type
        $this->assertTrue(
            $reflection->hasReturnType(),
            'render() method should have a return type'
        );

        $this->assertEquals(
            'string',
            $reflection->getReturnType()->getName(),
            'render() method should return string'
        );

        // Verify parameters
        $parameters = $reflection->getParameters();
        $this->assertCount(
            1,
            $parameters,
            'render() method should have exactly 1 parameter'
        );

        $this->assertEquals(
            'table',
            $parameters[0]->getName(),
            'render() method parameter should be named "table"'
        );

        $this->assertEquals(
            TableBuilder::class,
            $parameters[0]->getType()->getName(),
            'render() method parameter should be of type TableBuilder'
        );
    }

    /**
     * Test that TableEngineInterface has configure() method.
     *
     * @return void
     */
    #[Test]
    public function test_interface_has_configure_method(): void
    {
        $this->assertTrue(
            method_exists(TableEngineInterface::class, 'configure'),
            'TableEngineInterface should have configure() method'
        );

        $reflection = new \ReflectionMethod(TableEngineInterface::class, 'configure');

        // Verify return type
        $this->assertTrue(
            $reflection->hasReturnType(),
            'configure() method should have a return type'
        );

        $this->assertEquals(
            'void',
            $reflection->getReturnType()->getName(),
            'configure() method should return void'
        );

        // Verify parameters
        $parameters = $reflection->getParameters();
        $this->assertCount(
            1,
            $parameters,
            'configure() method should have exactly 1 parameter'
        );

        $this->assertEquals(
            'table',
            $parameters[0]->getName(),
            'configure() method parameter should be named "table"'
        );

        $this->assertEquals(
            TableBuilder::class,
            $parameters[0]->getType()->getName(),
            'configure() method parameter should be of type TableBuilder'
        );
    }

    /**
     * Test that TableEngineInterface has getAssets() method.
     *
     * @return void
     */
    #[Test]
    public function test_interface_has_get_assets_method(): void
    {
        $this->assertTrue(
            method_exists(TableEngineInterface::class, 'getAssets'),
            'TableEngineInterface should have getAssets() method'
        );

        $reflection = new \ReflectionMethod(TableEngineInterface::class, 'getAssets');

        // Verify return type
        $this->assertTrue(
            $reflection->hasReturnType(),
            'getAssets() method should have a return type'
        );

        $this->assertEquals(
            'array',
            $reflection->getReturnType()->getName(),
            'getAssets() method should return array'
        );

        // Verify no parameters
        $parameters = $reflection->getParameters();
        $this->assertCount(
            0,
            $parameters,
            'getAssets() method should have no parameters'
        );
    }

    /**
     * Test that TableEngineInterface has supports() method.
     *
     * @return void
     */
    #[Test]
    public function test_interface_has_supports_method(): void
    {
        $this->assertTrue(
            method_exists(TableEngineInterface::class, 'supports'),
            'TableEngineInterface should have supports() method'
        );

        $reflection = new \ReflectionMethod(TableEngineInterface::class, 'supports');

        // Verify return type
        $this->assertTrue(
            $reflection->hasReturnType(),
            'supports() method should have a return type'
        );

        $this->assertEquals(
            'bool',
            $reflection->getReturnType()->getName(),
            'supports() method should return bool'
        );

        // Verify parameters
        $parameters = $reflection->getParameters();
        $this->assertCount(
            1,
            $parameters,
            'supports() method should have exactly 1 parameter'
        );

        $this->assertEquals(
            'feature',
            $parameters[0]->getName(),
            'supports() method parameter should be named "feature"'
        );

        $this->assertEquals(
            'string',
            $parameters[0]->getType()->getName(),
            'supports() method parameter should be of type string'
        );
    }

    /**
     * Test that TableEngineInterface has getName() method.
     *
     * @return void
     */
    #[Test]
    public function test_interface_has_get_name_method(): void
    {
        $this->assertTrue(
            method_exists(TableEngineInterface::class, 'getName'),
            'TableEngineInterface should have getName() method'
        );

        $reflection = new \ReflectionMethod(TableEngineInterface::class, 'getName');

        // Verify return type
        $this->assertTrue(
            $reflection->hasReturnType(),
            'getName() method should have a return type'
        );

        $this->assertEquals(
            'string',
            $reflection->getReturnType()->getName(),
            'getName() method should return string'
        );

        // Verify no parameters
        $parameters = $reflection->getParameters();
        $this->assertCount(
            0,
            $parameters,
            'getName() method should have no parameters'
        );
    }

    /**
     * Test that TableEngineInterface has getVersion() method.
     *
     * @return void
     */
    #[Test]
    public function test_interface_has_get_version_method(): void
    {
        $this->assertTrue(
            method_exists(TableEngineInterface::class, 'getVersion'),
            'TableEngineInterface should have getVersion() method'
        );

        $reflection = new \ReflectionMethod(TableEngineInterface::class, 'getVersion');

        // Verify return type
        $this->assertTrue(
            $reflection->hasReturnType(),
            'getVersion() method should have a return type'
        );

        $this->assertEquals(
            'string',
            $reflection->getReturnType()->getName(),
            'getVersion() method should return string'
        );

        // Verify no parameters
        $parameters = $reflection->getParameters();
        $this->assertCount(
            0,
            $parameters,
            'getVersion() method should have no parameters'
        );
    }

    /**
     * Test that TableEngineInterface has processServerSide() method.
     *
     * @return void
     */
    #[Test]
    public function test_interface_has_process_server_side_method(): void
    {
        $this->assertTrue(
            method_exists(TableEngineInterface::class, 'processServerSide'),
            'TableEngineInterface should have processServerSide() method'
        );

        $reflection = new \ReflectionMethod(TableEngineInterface::class, 'processServerSide');

        // Verify return type
        $this->assertTrue(
            $reflection->hasReturnType(),
            'processServerSide() method should have a return type'
        );

        $this->assertEquals(
            'array',
            $reflection->getReturnType()->getName(),
            'processServerSide() method should return array'
        );

        // Verify parameters
        $parameters = $reflection->getParameters();
        $this->assertCount(
            1,
            $parameters,
            'processServerSide() method should have exactly 1 parameter'
        );

        $this->assertEquals(
            'table',
            $parameters[0]->getName(),
            'processServerSide() method parameter should be named "table"'
        );

        $this->assertEquals(
            TableBuilder::class,
            $parameters[0]->getType()->getName(),
            'processServerSide() method parameter should be of type TableBuilder'
        );
    }

    /**
     * Test that TableEngineInterface has getConfig() method.
     *
     * @return void
     */
    #[Test]
    public function test_interface_has_get_config_method(): void
    {
        $this->assertTrue(
            method_exists(TableEngineInterface::class, 'getConfig'),
            'TableEngineInterface should have getConfig() method'
        );

        $reflection = new \ReflectionMethod(TableEngineInterface::class, 'getConfig');

        // Verify return type
        $this->assertTrue(
            $reflection->hasReturnType(),
            'getConfig() method should have a return type'
        );

        $this->assertEquals(
            'array',
            $reflection->getReturnType()->getName(),
            'getConfig() method should return array'
        );

        // Verify no parameters
        $parameters = $reflection->getParameters();
        $this->assertCount(
            0,
            $parameters,
            'getConfig() method should have no parameters'
        );
    }

    /**
     * Test that TableEngineInterface has setConfig() method.
     *
     * @return void
     */
    #[Test]
    public function test_interface_has_set_config_method(): void
    {
        $this->assertTrue(
            method_exists(TableEngineInterface::class, 'setConfig'),
            'TableEngineInterface should have setConfig() method'
        );

        $reflection = new \ReflectionMethod(TableEngineInterface::class, 'setConfig');

        // Verify return type
        $this->assertTrue(
            $reflection->hasReturnType(),
            'setConfig() method should have a return type'
        );

        $this->assertEquals(
            'void',
            $reflection->getReturnType()->getName(),
            'setConfig() method should return void'
        );

        // Verify parameters
        $parameters = $reflection->getParameters();
        $this->assertCount(
            1,
            $parameters,
            'setConfig() method should have exactly 1 parameter'
        );

        $this->assertEquals(
            'config',
            $parameters[0]->getName(),
            'setConfig() method parameter should be named "config"'
        );

        $this->assertEquals(
            'array',
            $parameters[0]->getType()->getName(),
            'setConfig() method parameter should be of type array'
        );
    }

    /**
     * Test that all interface methods are implemented.
     *
     * This test verifies that the interface defines all required methods
     * for a complete table engine implementation.
     *
     * @return void
     */
    #[Test]
    public function test_all_interface_methods_are_implemented(): void
    {
        $reflection = new \ReflectionClass(TableEngineInterface::class);
        $methods = $reflection->getMethods();

        $expectedMethods = [
            'render',
            'configure',
            'getAssets',
            'supports',
            'getName',
            'getVersion',
            'processServerSide',
            'getConfig',
            'setConfig',
        ];

        $actualMethods = array_map(function ($method) {
            return $method->getName();
        }, $methods);

        foreach ($expectedMethods as $expectedMethod) {
            $this->assertContains(
                $expectedMethod,
                $actualMethods,
                "TableEngineInterface should have {$expectedMethod}() method"
            );
        }

        $this->assertCount(
            count($expectedMethods),
            $methods,
            'TableEngineInterface should have exactly ' . count($expectedMethods) . ' methods'
        );
    }

    /**
     * Test that interface methods have proper documentation.
     *
     * @return void
     */
    #[Test]
    public function test_interface_methods_have_documentation(): void
    {
        $reflection = new \ReflectionClass(TableEngineInterface::class);

        // Check class documentation
        $classDocComment = $reflection->getDocComment();
        $this->assertNotFalse(
            $classDocComment,
            'TableEngineInterface should have class-level documentation'
        );

        // Check each method has documentation
        $methods = $reflection->getMethods();
        foreach ($methods as $method) {
            $docComment = $method->getDocComment();
            $this->assertNotFalse(
                $docComment,
                "Method {$method->getName()}() should have documentation"
            );

            // Verify documentation contains @param for methods with parameters
            if (count($method->getParameters()) > 0) {
                $this->assertStringContainsString(
                    '@param',
                    $docComment,
                    "Method {$method->getName()}() should document its parameters"
                );
            }

            // Verify documentation contains @return
            $this->assertStringContainsString(
                '@return',
                $docComment,
                "Method {$method->getName()}() should document its return type"
            );
        }
    }
}
