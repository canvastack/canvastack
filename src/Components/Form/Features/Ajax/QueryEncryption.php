<?php

namespace Canvastack\Canvastack\Components\Form\Features\Ajax;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;

/**
 * QueryEncryption - Encrypts and decrypts query parameters for Ajax Sync.
 *
 * This class provides secure encryption and decryption of SQL queries and
 * parameters to prevent SQL injection and protect sensitive data during
 * Ajax requests.
 */
class QueryEncryption
{
    protected Encrypter $encrypter;

    public function __construct(Encrypter $encrypter)
    {
        $this->encrypter = $encrypter;
    }

    /**
     * Encrypt a value.
     *
     * Encrypts the given value using Laravel's encryption service.
     * Null values are converted to empty strings before encryption.
     *
     * @param mixed $value Value to encrypt
     * @return string Encrypted string
     */
    public function encrypt($value): string
    {
        if (is_null($value)) {
            return $this->encrypter->encrypt('');
        }

        return $this->encrypter->encrypt($value);
    }

    /**
     * Decrypt a value.
     *
     * Decrypts the given encrypted string using Laravel's encryption service.
     * Empty strings are converted back to null after decryption.
     *
     * @param string $encrypted Encrypted string
     * @return mixed Decrypted value
     * @throws DecryptException If decryption fails
     */
    public function decrypt(string $encrypted)
    {
        try {
            $decrypted = $this->encrypter->decrypt($encrypted);

            // Convert empty strings back to null
            return $decrypted === '' ? null : $decrypted;
        } catch (DecryptException $e) {
            throw new DecryptException('Failed to decrypt value: ' . $e->getMessage());
        }
    }

    /**
     * Validate that a string is properly encrypted.
     *
     * Attempts to decrypt the string to verify it's valid encrypted data.
     * Returns true if decryption succeeds, false otherwise.
     *
     * @param string $encrypted String to validate
     * @return bool True if valid encrypted string, false otherwise
     */
    public function isValid(string $encrypted): bool
    {
        try {
            $this->encrypter->decrypt($encrypted);

            return true;
        } catch (DecryptException $e) {
            return false;
        }
    }
}
