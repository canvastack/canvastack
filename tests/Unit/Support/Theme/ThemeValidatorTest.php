<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Theme;

use Canvastack\Canvastack\Support\Theme\ThemeValidator;
use Canvastack\Canvastack\Tests\TestCase;
use InvalidArgumentException;

class ThemeValidatorTest extends TestCase
{
    protected ThemeValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ThemeValidator();
    }

    /** @test */
    public function it_validates_a_valid_theme_configuration(): void
    {
        $config = [
            'name' => 'test-theme',
            'display_name' => 'Test Theme',
            'version' => '1.0.0',
            'author' => 'Test Author',
            'description' => 'A test theme',
            'config' => [
                'colors' => [
                    'primary' => '#6366f1',
                    'secondary' => '#8b5cf6',
                    'accent' => '#a855f7',
                ],
                'fonts' => [
                    'sans' => 'Inter',
                ],
            ],
        ];

        $result = $this->validator->validate($config);

        $this->assertTrue($result);
        $this->assertFalse($this->validator->hasErrors());
    }

    /** @test */
    public function it_fails_validation_when_required_fields_are_missing(): void
    {
        $config = [
            'name' => 'test-theme',
            // Missing other required fields
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result);
        $this->assertTrue($this->validator->hasErrors());
        $this->assertNotEmpty($this->validator->getErrors());
    }

    /** @test */
    public function it_validates_theme_name_format(): void
    {
        $config = [
            'name' => 'InvalidName',  // Should be kebab-case
            'display_name' => 'Test Theme',
            'version' => '1.0.0',
            'author' => 'Test Author',
            'description' => 'A test theme',
            'config' => [
                'colors' => [
                    'primary' => '#6366f1',
                    'secondary' => '#8b5cf6',
                    'accent' => '#a855f7',
                ],
                'fonts' => [
                    'sans' => 'Inter',
                ],
            ],
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result);
        $this->assertStringContainsString('kebab-case', $this->validator->getFirstError());
    }

    /** @test */
    public function it_validates_version_format(): void
    {
        $config = [
            'name' => 'test-theme',
            'display_name' => 'Test Theme',
            'version' => 'invalid',  // Should be semver
            'author' => 'Test Author',
            'description' => 'A test theme',
            'config' => [
                'colors' => [
                    'primary' => '#6366f1',
                    'secondary' => '#8b5cf6',
                    'accent' => '#a855f7',
                ],
                'fonts' => [
                    'sans' => 'Inter',
                ],
            ],
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result);
        $this->assertStringContainsString('semver', $this->validator->getFirstError());
    }

    /** @test */
    public function it_validates_required_colors(): void
    {
        $config = [
            'name' => 'test-theme',
            'display_name' => 'Test Theme',
            'version' => '1.0.0',
            'author' => 'Test Author',
            'description' => 'A test theme',
            'config' => [
                'colors' => [
                    'primary' => '#6366f1',
                    // Missing secondary and accent
                ],
                'fonts' => [
                    'sans' => 'Inter',
                ],
            ],
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result);
        $this->assertTrue($this->validator->hasErrors());
    }

    /** @test */
    public function it_validates_hex_color_format(): void
    {
        $config = [
            'name' => 'test-theme',
            'display_name' => 'Test Theme',
            'version' => '1.0.0',
            'author' => 'Test Author',
            'description' => 'A test theme',
            'config' => [
                'colors' => [
                    'primary' => 'invalid-color',
                    'secondary' => '#8b5cf6',
                    'accent' => '#a855f7',
                ],
                'fonts' => [
                    'sans' => 'Inter',
                ],
            ],
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result);
        $this->assertStringContainsString('hex color', $this->validator->getFirstError());
    }

    /** @test */
    public function it_validates_required_fonts(): void
    {
        $config = [
            'name' => 'test-theme',
            'display_name' => 'Test Theme',
            'version' => '1.0.0',
            'author' => 'Test Author',
            'description' => 'A test theme',
            'config' => [
                'colors' => [
                    'primary' => '#6366f1',
                    'secondary' => '#8b5cf6',
                    'accent' => '#a855f7',
                ],
                'fonts' => [
                    'mono' => 'Courier',
                    // Missing 'sans' font
                ],
            ],
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result);
        $errors = $this->validator->getErrors();
        $this->assertNotEmpty($errors);

        // Check that one of the errors mentions 'sans'
        $hasSansError = false;
        foreach ($errors as $error) {
            if (str_contains($error, 'sans')) {
                $hasSansError = true;
                break;
            }
        }
        $this->assertTrue($hasSansError, 'Expected validation error about missing sans font');
    }

    /** @test */
    public function it_throws_exception_when_validate_or_fail_is_called(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Theme validation failed');

        $config = [
            'name' => 'test-theme',
            // Missing required fields
        ];

        $this->validator->validateOrFail($config);
    }

    /** @test */
    public function it_can_get_validation_errors_as_string(): void
    {
        $config = [
            'name' => 'test-theme',
        ];

        $this->validator->validate($config);

        $errorsString = $this->validator->getErrorsAsString();

        $this->assertIsString($errorsString);
        $this->assertNotEmpty($errorsString);
    }

    /** @test */
    public function it_can_set_custom_validation_rules(): void
    {
        $customRules = [
            'required_fields' => ['name', 'config'],
            'required_config' => ['colors'],
            'required_colors' => ['primary'],
        ];

        $this->validator->setRules($customRules);

        $config = [
            'name' => 'test-theme',
            'config' => [
                'colors' => [
                    'primary' => '#6366f1',
                ],
            ],
        ];

        $result = $this->validator->validate($config);

        // With relaxed rules, this should pass
        $this->assertTrue($result, 'Validation should pass with custom relaxed rules. Errors: ' . $this->validator->getErrorsAsString());
    }
}
