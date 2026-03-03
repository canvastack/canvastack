<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Property\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\Support\PropertyTesting\Generator;
use Canvastack\Canvastack\Tests\Support\PropertyTesting\PropertyTestCase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Property 4: Malicious Attribute Rejection.
 *
 * Validates: Requirements 24.2
 *
 * Property: For ALL HTML attributes that are event handlers (onclick, onload, etc.)
 * or contain javascript:/data: URLs, the addAttributes() method MUST throw
 * InvalidArgumentException.
 *
 * This property ensures that malicious HTML attributes cannot be injected
 * to execute JavaScript code.
 */
class MaliciousAttributeRejectionPropertyTest extends PropertyTestCase
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
     * Property 4.1: Event handler attributes are rejected.
     *
     * @test
     * @group property
     * @group security
     * @group canvastack-table-complete
     */
    public function property_event_handler_attributes_are_rejected(): void
    {
        $this->forAllExpectingException(
            Generator::maliciousAttributes(),
            function (array $attributes) {
                $this->table->addAttributes($attributes);
            },
            InvalidArgumentException::class,
            100
        );
    }

    /**
     * Property 4.2: onclick attribute is rejected.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_onclick_attribute_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->table->addAttributes(['onclick' => 'alert("XSS")']);
    }

    /**
     * Property 4.3: onload attribute is rejected.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_onload_attribute_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->table->addAttributes(['onload' => 'alert("XSS")']);
    }

    /**
     * Property 4.4: onerror attribute is rejected.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_onerror_attribute_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->table->addAttributes(['onerror' => 'alert("XSS")']);
    }

    /**
     * Property 4.5: onmouseover attribute is rejected.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_onmouseover_attribute_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->table->addAttributes(['onmouseover' => 'alert("XSS")']);
    }

    /**
     * Property 4.6: javascript: URLs are rejected.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_javascript_urls_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->table->addAttributes(['href' => 'javascript:alert("XSS")']);
    }

    /**
     * Property 4.7: data: URLs are rejected.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_data_urls_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->table->addAttributes(['src' => 'data:text/html,<script>alert("XSS")</script>']);
    }

    /**
     * Property 4.8: All event handler variations are rejected.
     *
     * Test various event handler attributes.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_all_event_handlers_are_rejected(): void
    {
        $eventHandlers = [
            'onclick', 'ondblclick', 'onmousedown', 'onmouseup', 'onmouseover',
            'onmousemove', 'onmouseout', 'onmouseenter', 'onmouseleave',
            'onload', 'onunload', 'onchange', 'onsubmit', 'onreset',
            'onselect', 'onblur', 'onfocus', 'onkeydown', 'onkeypress',
            'onkeyup', 'onerror', 'onabort', 'oncanplay', 'oncanplaythrough',
        ];

        $this->forAllExpectingException(
            Generator::elements($eventHandlers),
            function (string $handler) {
                $this->table->addAttributes([$handler => 'alert("XSS")']);
            },
            InvalidArgumentException::class,
            100
        );
    }

    /**
     * Property 4.9: Case-insensitive event handler rejection.
     *
     * Test that event handlers are rejected regardless of case.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_case_insensitive_event_handler_rejection(): void
    {
        $variations = ['onclick', 'onClick', 'ONCLICK', 'OnClick', 'oNcLiCk'];

        $this->forAllExpectingException(
            Generator::elements($variations),
            function (string $handler) {
                $this->table->addAttributes([$handler => 'alert("XSS")']);
            },
            InvalidArgumentException::class,
            100
        );
    }

    /**
     * Property 4.10: Safe attributes are accepted.
     *
     * Test that safe attributes do NOT throw exceptions.
     *
     * @test
     * @group property
     */
    public function property_safe_attributes_are_accepted(): void
    {
        $safeAttributes = [
            ['class' => 'table table-striped'],
            ['id' => 'my-table'],
            ['data-toggle' => 'tooltip'],
            ['aria-label' => 'User Table'],
            ['role' => 'grid'],
            ['style' => 'width: 100%'],
        ];

        $this->forAll(
            Generator::elements($safeAttributes),
            function (array $attributes) {
                try {
                    $this->table->addAttributes($attributes);

                    return true;
                } catch (\Exception $e) {
                    return false;
                }
            },
            100
        );
    }

    /**
     * Property 4.11: Multiple malicious attributes are rejected.
     *
     * Test that if any attribute is malicious, all are rejected.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_multiple_malicious_attributes_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->table->addAttributes([
            'class' => 'table',
            'onclick' => 'alert("XSS")',
            'id' => 'my-table',
        ]);
    }

    /**
     * Property 4.12: vbscript: URLs are rejected.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_vbscript_urls_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->table->addAttributes(['href' => 'vbscript:msgbox("XSS")']);
    }
}
