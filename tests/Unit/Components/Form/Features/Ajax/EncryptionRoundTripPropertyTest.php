<?php

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Features\Ajax;

use Canvastack\Canvastack\Components\Form\Features\Ajax\QueryEncryption;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Encryption\Encrypter;

/**
 * Property Test 5: Ajax Sync Encryption Round-Trip.
 *
 * Validates Requirements: 2.3, 2.5, 14.1
 *
 * Property: For any value V, decrypt(encrypt(V)) = V
 *
 * This property test ensures that:
 * 1. Any value can be encrypted and then decrypted back to its original form
 * 2. Encryption is reversible and data integrity is maintained
 * 3. Null values are handled correctly
 * 4. Different data types are preserved through encryption/decryption
 */
class EncryptionRoundTripPropertyTest extends TestCase
{
    protected QueryEncryption $encryption;

    protected function setUp(): void
    {
        parent::setUp();

        // Create encryption instance with Laravel's encrypter
        $key = 'base64:' . base64_encode(random_bytes(32));
        $encrypter = new Encrypter(base64_decode(substr($key, 7)), 'AES-256-CBC');
        $this->encryption = new QueryEncryption($encrypter);
    }

    /**
     * Property Test: Encryption round-trip preserves string values.
     *
     * @test
     */
    public function test_encryption_round_trip_preserves_string_values(): void
    {
        // Generate test strings
        $testStrings = [
            'simple string',
            'SELECT * FROM cities WHERE province_id = ?',
            'String with special chars: !@#$%^&*()',
            'Unicode string: 你好世界 🌍',
            'Long string: ' . str_repeat('a', 1000),
            '',
        ];

        foreach ($testStrings as $original) {
            $encrypted = $this->encryption->encrypt($original);
            $decrypted = $this->encryption->decrypt($encrypted);

            $this->assertEquals(
                $original,
                $decrypted,
                "Encryption round-trip failed for string: {$original}"
            );

            // Verify encrypted value is different from original
            if ($original !== '') {
                $this->assertNotEquals($original, $encrypted);
            }
        }
    }

    /**
     * Property Test: Encryption round-trip preserves numeric values.
     *
     * @test
     */
    public function test_encryption_round_trip_preserves_numeric_values(): void
    {
        $testNumbers = [
            0,
            1,
            -1,
            42,
            999999,
            3.14159,
            -273.15,
        ];

        foreach ($testNumbers as $original) {
            $encrypted = $this->encryption->encrypt($original);
            $decrypted = $this->encryption->decrypt($encrypted);

            $this->assertEquals(
                $original,
                $decrypted,
                "Encryption round-trip failed for number: {$original}"
            );
        }
    }

    /**
     * Property Test: Encryption round-trip handles null values correctly.
     *
     * @test
     */
    public function test_encryption_round_trip_handles_null_values(): void
    {
        $encrypted = $this->encryption->encrypt(null);
        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertNull(
            $decrypted,
            'Encryption round-trip failed for null value'
        );
    }

    /**
     * Property Test: Encryption round-trip preserves array values.
     *
     * @test
     */
    public function test_encryption_round_trip_preserves_array_values(): void
    {
        $testArrays = [
            ['a', 'b', 'c'],
            ['key' => 'value', 'foo' => 'bar'],
            [1, 2, 3, 4, 5],
            ['nested' => ['array' => ['structure']]],
        ];

        foreach ($testArrays as $original) {
            $encrypted = $this->encryption->encrypt($original);
            $decrypted = $this->encryption->decrypt($encrypted);

            $this->assertEquals(
                $original,
                $decrypted,
                'Encryption round-trip failed for array: ' . json_encode($original)
            );
        }
    }

    /**
     * Property Test: Multiple encryptions of same value produce different ciphertexts.
     *
     * This ensures that encryption uses proper initialization vectors (IV)
     * and doesn't produce predictable patterns.
     *
     * @test
     */
    public function test_multiple_encryptions_produce_different_ciphertexts(): void
    {
        $value = 'test value';

        $encrypted1 = $this->encryption->encrypt($value);
        $encrypted2 = $this->encryption->encrypt($value);

        // Different ciphertexts
        $this->assertNotEquals(
            $encrypted1,
            $encrypted2,
            'Multiple encryptions should produce different ciphertexts'
        );

        // But both decrypt to same value
        $this->assertEquals($value, $this->encryption->decrypt($encrypted1));
        $this->assertEquals($value, $this->encryption->decrypt($encrypted2));
    }

    /**
     * Property Test: Encryption validation works correctly.
     *
     * @test
     */
    public function test_encryption_validation_works_correctly(): void
    {
        $value = 'test value';
        $encrypted = $this->encryption->encrypt($value);

        // Valid encrypted string
        $this->assertTrue(
            $this->encryption->isValid($encrypted),
            'Valid encrypted string should pass validation'
        );

        // Invalid encrypted strings
        $invalidStrings = [
            'not encrypted',
            'random string',
            '',
            'base64encodedbutnotencrypted',
        ];

        foreach ($invalidStrings as $invalid) {
            $this->assertFalse(
                $this->encryption->isValid($invalid),
                "Invalid string should fail validation: {$invalid}"
            );
        }
    }

    /**
     * Property Test: Encryption is deterministic for decryption.
     *
     * The same encrypted value should always decrypt to the same result.
     *
     * @test
     */
    public function test_decryption_is_deterministic(): void
    {
        $value = 'test value';
        $encrypted = $this->encryption->encrypt($value);

        // Decrypt multiple times
        $decrypted1 = $this->encryption->decrypt($encrypted);
        $decrypted2 = $this->encryption->decrypt($encrypted);
        $decrypted3 = $this->encryption->decrypt($encrypted);

        $this->assertEquals($decrypted1, $decrypted2);
        $this->assertEquals($decrypted2, $decrypted3);
        $this->assertEquals($value, $decrypted1);
    }

    /**
     * Property Test: Encryption handles SQL queries correctly.
     *
     * This is critical for Ajax Sync security.
     *
     * @test
     */
    public function test_encryption_handles_sql_queries_correctly(): void
    {
        $queries = [
            'SELECT id, name FROM cities WHERE province_id = ?',
            'SELECT * FROM users WHERE status = ? AND role = ?',
            'SELECT DISTINCT category FROM products WHERE active = ?',
        ];

        foreach ($queries as $query) {
            $encrypted = $this->encryption->encrypt($query);
            $decrypted = $this->encryption->decrypt($encrypted);

            $this->assertEquals(
                $query,
                $decrypted,
                "SQL query encryption round-trip failed: {$query}"
            );

            // Verify encrypted query doesn't contain SQL keywords
            $this->assertStringNotContainsString('SELECT', $encrypted);
            $this->assertStringNotContainsString('WHERE', $encrypted);
        }
    }

    /**
     * Performance Test: Encryption round-trip completes within acceptable time.
     *
     * Validates Requirement 14.1 (Security performance)
     *
     * @test
     */
    public function test_encryption_round_trip_performance(): void
    {
        $value = 'SELECT * FROM cities WHERE province_id = ?';
        $iterations = 100;

        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $encrypted = $this->encryption->encrypt($value);
            $decrypted = $this->encryption->decrypt($encrypted);
        }

        $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds
        $avgDuration = $duration / $iterations;

        // Each round-trip should complete in less than 5ms
        $this->assertLessThan(
            5,
            $avgDuration,
            "Encryption round-trip took {$avgDuration}ms, expected < 5ms"
        );
    }
}
