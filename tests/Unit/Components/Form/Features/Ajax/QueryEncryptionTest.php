<?php

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Features\Ajax;

use Canvastack\Canvastack\Components\Form\Features\Ajax\QueryEncryption;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;

/**
 * Unit Tests for QueryEncryption.
 *
 * Tests Requirements: 2.3, 2.4, 2.5
 *
 * These tests verify:
 * - Encryption and decryption functionality
 * - Validation of encrypted strings
 * - Handling of null values
 * - Error handling for invalid encrypted data
 */
class QueryEncryptionTest extends TestCase
{
    protected QueryEncryption $encryption;

    protected Encrypter $encrypter;

    protected function setUp(): void
    {
        parent::setUp();

        // Create encryption instance with Laravel's encrypter
        $key = 'base64:' . base64_encode(random_bytes(32));
        $this->encrypter = new Encrypter(base64_decode(substr($key, 7)), 'AES-256-CBC');
        $this->encryption = new QueryEncryption($this->encrypter);
    }

    /**
     * Test: Can encrypt a string value.
     *
     * @test
     */
    public function test_can_encrypt_string_value(): void
    {
        $value = 'test string';
        $encrypted = $this->encryption->encrypt($value);

        $this->assertIsString($encrypted);
        $this->assertNotEmpty($encrypted);
        $this->assertNotEquals($value, $encrypted);
    }

    /**
     * Test: Can decrypt an encrypted string.
     *
     * @test
     */
    public function test_can_decrypt_encrypted_string(): void
    {
        $value = 'test string';
        $encrypted = $this->encryption->encrypt($value);
        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertEquals($value, $decrypted);
    }

    /**
     * Test: Can encrypt and decrypt SQL queries.
     *
     * Validates Requirement 2.3 (Query encryption)
     *
     * @test
     */
    public function test_can_encrypt_and_decrypt_sql_queries(): void
    {
        $query = 'SELECT id, name FROM cities WHERE province_id = ?';
        $encrypted = $this->encryption->encrypt($query);
        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertEquals($query, $decrypted);
        $this->assertStringNotContainsString('SELECT', $encrypted);
        $this->assertStringNotContainsString('WHERE', $encrypted);
    }

    /**
     * Test: Handles null values correctly.
     *
     * Validates Requirement 2.4 (Null value handling)
     *
     * @test
     */
    public function test_handles_null_values_correctly(): void
    {
        $encrypted = $this->encryption->encrypt(null);
        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertNull($decrypted);
    }

    /**
     * Test: Handles empty string values.
     *
     * @test
     */
    public function test_handles_empty_string_values(): void
    {
        $encrypted = $this->encryption->encrypt('');
        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertEquals('', $decrypted);
    }

    /**
     * Test: Can encrypt numeric values.
     *
     * @test
     */
    public function test_can_encrypt_numeric_values(): void
    {
        $values = [0, 1, 42, -1, 3.14];

        foreach ($values as $value) {
            $encrypted = $this->encryption->encrypt($value);
            $decrypted = $this->encryption->decrypt($encrypted);

            $this->assertEquals($value, $decrypted);
        }
    }

    /**
     * Test: Can encrypt array values.
     *
     * @test
     */
    public function test_can_encrypt_array_values(): void
    {
        $array = ['key' => 'value', 'foo' => 'bar'];
        $encrypted = $this->encryption->encrypt($array);
        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertEquals($array, $decrypted);
    }

    /**
     * Test: Validates encrypted strings correctly.
     *
     * Validates Requirement 2.5 (Validation of encrypted data)
     *
     * @test
     */
    public function test_validates_encrypted_strings_correctly(): void
    {
        $value = 'test value';
        $encrypted = $this->encryption->encrypt($value);

        $this->assertTrue($this->encryption->isValid($encrypted));
    }

    /**
     * Test: Rejects invalid encrypted strings.
     *
     * Validates Requirement 2.5 (Validation of encrypted data)
     *
     * @test
     */
    public function test_rejects_invalid_encrypted_strings(): void
    {
        $invalidStrings = [
            'not encrypted',
            'random string',
            'base64encodedbutnotencrypted',
            '12345',
        ];

        foreach ($invalidStrings as $invalid) {
            $this->assertFalse(
                $this->encryption->isValid($invalid),
                "Should reject invalid string: {$invalid}"
            );
        }
    }

    /**
     * Test: Rejects empty string as invalid encrypted data.
     *
     * @test
     */
    public function test_rejects_empty_string_as_invalid(): void
    {
        $this->assertFalse($this->encryption->isValid(''));
    }

    /**
     * Test: Throws exception when decrypting invalid data.
     *
     * @test
     */
    public function test_throws_exception_when_decrypting_invalid_data(): void
    {
        $this->expectException(DecryptException::class);

        $this->encryption->decrypt('invalid encrypted data');
    }

    /**
     * Test: Different encryptions of same value produce different ciphertexts.
     *
     * This ensures proper use of initialization vectors (IV)
     *
     * @test
     */
    public function test_different_encryptions_produce_different_ciphertexts(): void
    {
        $value = 'test value';

        $encrypted1 = $this->encryption->encrypt($value);
        $encrypted2 = $this->encryption->encrypt($value);

        $this->assertNotEquals($encrypted1, $encrypted2);
    }

    /**
     * Test: Same encrypted value always decrypts to same result.
     *
     * @test
     */
    public function test_decryption_is_deterministic(): void
    {
        $value = 'test value';
        $encrypted = $this->encryption->encrypt($value);

        $decrypted1 = $this->encryption->decrypt($encrypted);
        $decrypted2 = $this->encryption->decrypt($encrypted);
        $decrypted3 = $this->encryption->decrypt($encrypted);

        $this->assertEquals($decrypted1, $decrypted2);
        $this->assertEquals($decrypted2, $decrypted3);
        $this->assertEquals($value, $decrypted1);
    }

    /**
     * Test: Handles special characters in encryption.
     *
     * @test
     */
    public function test_handles_special_characters(): void
    {
        $specialStrings = [
            'String with "quotes"',
            "String with 'single quotes'",
            'String with \backslashes\\',
            'String with special chars: !@#$%^&*()',
            'String with newlines\nand\ttabs',
        ];

        foreach ($specialStrings as $value) {
            $encrypted = $this->encryption->encrypt($value);
            $decrypted = $this->encryption->decrypt($encrypted);

            $this->assertEquals($value, $decrypted);
        }
    }

    /**
     * Test: Handles Unicode characters in encryption.
     *
     * @test
     */
    public function test_handles_unicode_characters(): void
    {
        $unicodeStrings = [
            '你好世界',
            'مرحبا بالعالم',
            'Привет мир',
            '🌍🌎🌏',
            'Café résumé naïve',
        ];

        foreach ($unicodeStrings as $value) {
            $encrypted = $this->encryption->encrypt($value);
            $decrypted = $this->encryption->decrypt($encrypted);

            $this->assertEquals($value, $decrypted);
        }
    }

    /**
     * Test: Handles long strings in encryption.
     *
     * @test
     */
    public function test_handles_long_strings(): void
    {
        $longString = str_repeat('a', 10000);

        $encrypted = $this->encryption->encrypt($longString);
        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertEquals($longString, $decrypted);
    }

    /**
     * Test: Encryption uses Laravel's encrypter correctly.
     *
     * @test
     */
    public function test_uses_laravel_encrypter_correctly(): void
    {
        $value = 'test value';

        // Encrypt using QueryEncryption
        $encrypted = $this->encryption->encrypt($value);

        // Should be able to decrypt using Laravel's encrypter directly
        $decrypted = $this->encrypter->decrypt($encrypted);

        $this->assertEquals($value, $decrypted);
    }

    /**
     * Test: Can decrypt values encrypted by Laravel's encrypter.
     *
     * @test
     */
    public function test_can_decrypt_laravel_encrypted_values(): void
    {
        $value = 'test value';

        // Encrypt using Laravel's encrypter directly
        $encrypted = $this->encrypter->encrypt($value);

        // Should be able to decrypt using QueryEncryption
        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertEquals($value, $decrypted);
    }

    /**
     * Test: Encryption performance is acceptable.
     *
     * @test
     */
    public function test_encryption_performance_is_acceptable(): void
    {
        $value = 'SELECT * FROM cities WHERE province_id = ?';
        $iterations = 100;

        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->encryption->encrypt($value);
        }

        $duration = (microtime(true) - $start) * 1000;
        $avgDuration = $duration / $iterations;

        // Each encryption should complete in less than 2ms
        $this->assertLessThan(2, $avgDuration);
    }

    /**
     * Test: Decryption performance is acceptable.
     *
     * @test
     */
    public function test_decryption_performance_is_acceptable(): void
    {
        $value = 'SELECT * FROM cities WHERE province_id = ?';
        $encrypted = $this->encryption->encrypt($value);
        $iterations = 100;

        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->encryption->decrypt($encrypted);
        }

        $duration = (microtime(true) - $start) * 1000;
        $avgDuration = $duration / $iterations;

        // Each decryption should complete in less than 2ms
        $this->assertLessThan(2, $avgDuration);
    }

    /**
     * Test: Validation performance is acceptable.
     *
     * @test
     */
    public function test_validation_performance_is_acceptable(): void
    {
        $value = 'test value';
        $encrypted = $this->encryption->encrypt($value);
        $iterations = 100;

        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->encryption->isValid($encrypted);
        }

        $duration = (microtime(true) - $start) * 1000;
        $avgDuration = $duration / $iterations;

        // Each validation should complete in less than 2ms
        $this->assertLessThan(2, $avgDuration);
    }
}
