<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table;

/**
 * Hash Generator Component
 * 
 * Generates secure, unique, non-predictable identifiers for table instances
 * using SHA256 hashing with cryptographically secure random bytes.
 * 
 * @package Canvastack\Canvastack\Components\Table
 */
class HashGenerator
{
    /**
     * Global instance counter for uniqueness across all table instances.
     * 
     * @var int
     */
    private static int $instanceCounter = 0;

    /**
     * Generate a unique table ID.
     * 
     * Creates a secure unique identifier using SHA256 hash algorithm.
     * The hash includes:
     * - Table name or model class
     * - Database connection name
     * - Global instance counter
     * - Field list (serialized)
     * - Current microtime for temporal uniqueness
     * - Cryptographically secure random bytes
     * 
     * Format: canvastable_{16-character-hash}
     * 
     * Security considerations:
     * - Uses random_bytes() for cryptographic security
     * - SHA256 prevents reverse engineering of inputs
     * - No predictable patterns in output
     * - Does not expose table names or database structure
     * 
     * @param string $tableName Table name or model class name
     * @param string $connectionName Database connection name
     * @param array $fields List of field names
     * @return string Unique ID in format: canvastable_{16-char-hash}
     */
    public function generate(
        string $tableName,
        string $connectionName,
        array $fields
    ): string {
        // Increment global instance counter for uniqueness
        $instanceNumber = $this->getNextInstanceNumber();

        // Collect all inputs for hashing
        $inputs = [
            'table' => $tableName,
            'connection' => $connectionName,
            'instance' => $instanceNumber,
            'fields' => serialize($fields),
            'microtime' => microtime(true),
            'random' => $this->generateRandomBytes(16),
        ];

        // Create SHA256 hash from inputs
        $hash = $this->createHash($inputs);

        // Truncate to 16 characters and prepend prefix
        $truncatedHash = $this->truncateHash($hash, 16);

        return 'canvastable_' . $truncatedHash;
    }

    /**
     * Create SHA256 hash from inputs.
     * 
     * Concatenates all input values with a delimiter and generates
     * a SHA256 hash to ensure security and prevent reverse engineering.
     * 
     * @param array $inputs Array of values to hash
     * @return string 64-character hexadecimal hash string
     */
    protected function createHash(array $inputs): string
    {
        // Concatenate all inputs with delimiter
        $concatenated = implode('|', $inputs);

        // Generate SHA256 hash
        return hash('sha256', $concatenated);
    }

    /**
     * Generate cryptographically secure random bytes.
     * 
     * Uses random_bytes() which provides cryptographically secure
     * pseudo-random bytes suitable for security-sensitive operations.
     * 
     * @param int $length Number of bytes to generate
     * @return string Hex-encoded random bytes
     * @throws \Exception If secure random bytes cannot be generated
     */
    protected function generateRandomBytes(int $length = 16): string
    {
        try {
            $randomBytes = random_bytes($length);
            return bin2hex($randomBytes);
        } catch (\Exception $e) {
            // If random_bytes fails, throw exception
            // This should never happen in modern PHP installations
            throw new \RuntimeException(
                'Failed to generate cryptographically secure random bytes: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Increment and return instance counter.
     * 
     * Provides a globally unique sequential number for each table instance
     * created during the request lifecycle. This ensures uniqueness even
     * when other inputs are identical.
     * 
     * Thread-safe in single-threaded PHP environment.
     * 
     * @return int Current counter value before increment
     */
    protected function getNextInstanceNumber(): int
    {
        return ++self::$instanceCounter;
    }

    /**
     * Truncate hash to specified length.
     * 
     * Takes the first N characters of the hash string.
     * Default length is 16 characters for a good balance between
     * uniqueness and brevity.
     * 
     * @param string $hash Full hash string
     * @param int $length Desired length (default: 16)
     * @return string Truncated hash
     */
    protected function truncateHash(string $hash, int $length = 16): string
    {
        return substr($hash, 0, $length);
    }

    /**
     * Reset instance counter.
     * 
     * Resets the global instance counter to zero.
     * Useful for testing purposes.
     * 
     * @internal This method is for testing only
     * @return void
     */
    public static function resetCounter(): void
    {
        self::$instanceCounter = 0;
    }

    /**
     * Get current instance counter value.
     * 
     * Returns the current value of the instance counter.
     * Useful for testing and debugging.
     * 
     * @internal This method is for testing only
     * @return int Current counter value
     */
    public static function getCounter(): int
    {
        return self::$instanceCounter;
    }
}
