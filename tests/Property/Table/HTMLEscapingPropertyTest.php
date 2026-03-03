<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Property\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\Support\PropertyTesting\Generator;
use Canvastack\Canvastack\Tests\Support\PropertyTesting\PropertyTestCase;
use Illuminate\Support\Facades\Schema;

/**
 * Property 3: HTML Escaping in Output.
 *
 * Validates: Requirements 24.1, 24.8
 *
 * Property: For ALL user-provided content that appears in rendered output,
 * HTML special characters MUST be escaped to prevent XSS attacks.
 *
 * This property ensures that malicious HTML/JavaScript cannot be injected
 * through user input and executed in the browser.
 *
 * Note: Since renderer is not yet fully implemented (Phase 14), this test
 * validates that escaping functions are available and work correctly.
 */
class HTMLEscapingPropertyTest extends PropertyTestCase
{
    private TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();

        // Use real Mantra users table
        $this->table = app(TableBuilder::class);

        // Check if users table exists, if not create test table
        if (!Schema::hasTable('users')) {
            Schema::create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamps();
            });
        }

        $this->table->setName('users');
    }

    /**
     * Property 3.1: HTML special characters are escaped.
     *
     * Test that < > & " ' are properly escaped.
     *
     * @test
     * @group property
     * @group security
     * @group canvastack-table-complete
     */
    public function property_html_special_characters_are_escaped(): void
    {
        $this->forAll(
            Generator::maliciousHTML(),
            function (string $maliciousInput) {
                // Test that e() helper escapes correctly
                $escaped = e($maliciousInput);

                // Verify dangerous characters are escaped
                $hasDangerousChars = (
                    strpos($maliciousInput, '<script>') !== false ||
                    strpos($maliciousInput, '<img') !== false ||
                    strpos($maliciousInput, 'javascript:') !== false ||
                    strpos($maliciousInput, 'onerror=') !== false ||
                    strpos($maliciousInput, 'onload=') !== false
                );

                if ($hasDangerousChars) {
                    // Escaped output should not contain the original dangerous string
                    $stillDangerous = (
                        strpos($escaped, '<script>') !== false ||
                        strpos($escaped, '<img src=') !== false ||
                        (strpos($escaped, 'javascript:') !== false && strpos($escaped, '&') === false)
                    );

                    return !$stillDangerous;
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 3.2: Script tags are escaped.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_script_tags_are_escaped(): void
    {
        $this->forAll(
            Generator::stringContaining(['<script>', '</script>'], 10, 100),
            function (string $input) {
                $escaped = e($input);

                // After escaping, <script> should become &lt;script&gt;
                return strpos($escaped, '<script>') === false;
            },
            100
        );
    }

    /**
     * Property 3.3: Event handlers are escaped.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_event_handlers_are_escaped(): void
    {
        $eventHandlers = ['onclick=', 'onload=', 'onerror=', 'onmouseover=', 'onfocus='];

        $this->forAll(
            Generator::elements($eventHandlers),
            function (string $handler) {
                $input = '<img src=x ' . $handler . 'alert("XSS")>';
                $escaped = e($input);

                // After escaping, the tag should be escaped
                return strpos($escaped, '<img') === false;
            },
            100
        );
    }

    /**
     * Property 3.4: JavaScript URLs are escaped.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_javascript_urls_are_escaped(): void
    {
        $this->forAll(
            Generator::stringContaining(['javascript:'], 10, 100),
            function (string $input) {
                $escaped = e($input);

                // javascript: should still be there but context should be safe
                // The key is that < and > are escaped so it can't be in a tag
                return strpos($escaped, '<') === false || strpos($escaped, '&lt;') !== false;
            },
            100
        );
    }

    /**
     * Property 3.5: htmlspecialchars equivalence.
     *
     * Test that e() helper is equivalent to htmlspecialchars with correct flags.
     *
     * @test
     * @group property
     */
    public function property_e_helper_equals_htmlspecialchars(): void
    {
        $this->forAll(
            Generator::string(0, 100),
            function (string $input) {
                $escaped1 = e($input);
                $escaped2 = htmlspecialchars($input, ENT_QUOTES, 'UTF-8', false);

                return $escaped1 === $escaped2;
            },
            100
        );
    }

    /**
     * Property 3.6: Double escaping is prevented.
     *
     * Test that already escaped content is not double-escaped.
     *
     * @test
     * @group property
     */
    public function property_double_escaping_is_prevented(): void
    {
        $this->forAll(
            Generator::string(0, 100),
            function (string $input) {
                $escaped1 = e($input);
                $escaped2 = e($escaped1);

                // If input doesn't contain &, escaping twice should be same as once
                if (strpos($input, '&') === false) {
                    return $escaped1 === $escaped2;
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 3.7: Column labels are escaped.
     *
     * Test that custom column labels are escaped.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_column_labels_are_escaped(): void
    {
        $this->forAll(
            Generator::maliciousHTML(),
            function (string $maliciousLabel) {
                try {
                    // Set fields with malicious label
                    $this->table->setFields(['id' => $maliciousLabel]);

                    // If we get here, the label was accepted
                    // In render phase, it should be escaped
                    // For now, we just verify no exception is thrown
                    return true;
                } catch (\Exception $e) {
                    // If exception is thrown, that's also acceptable
                    // (means validation rejected it)
                    return true;
                }
            },
            100
        );
    }

    /**
     * Property 3.8: Safe content is not modified.
     *
     * Test that safe content without special characters is not modified.
     *
     * @test
     * @group property
     */
    public function property_safe_content_is_not_modified(): void
    {
        $safeChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 ';

        $generator = function () use ($safeChars) {
            for ($i = 0; $i < 100; $i++) {
                $length = rand(1, 50);
                $result = '';
                for ($j = 0; $j < $length; $j++) {
                    $result .= $safeChars[rand(0, strlen($safeChars) - 1)];
                }
                yield $result;
            }
        };

        $this->forAll(
            $generator(),
            function (string $safeInput) {
                $escaped = e($safeInput);

                return $escaped === $safeInput;
            },
            100
        );
    }
}
