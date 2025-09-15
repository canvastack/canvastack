<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Security;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Str;
use Canvastack\Canvastack\Library\Components\Table\Exceptions\SecurityException;

/**
 * DataEncryptionManager
 * 
 * Field-level encryption with key management and secure key rotation
 * Implements AES-256 encryption, database encryption support, decryption mechanisms
 * 
 * @package Canvastack\Table\Security
 * @version 2.0
 * @author Security Hardening Team
 */
class DataEncryptionManager
{
    /**
     * Encryption algorithms supported
     */
    private const SUPPORTED_ALGORITHMS = [
        'AES-256-CBC' => [
            'key_length' => 32,
            'iv_length' => 16,
            'block_size' => 16
        ],
        'AES-256-GCM' => [
            'key_length' => 32,
            'iv_length' => 12,
            'block_size' => 16,
            'tag_length' => 16
        ],
        'ChaCha20-Poly1305' => [
            'key_length' => 32,
            'iv_length' => 12,
            'block_size' => 64
        ]
    ];
    
    /**
     * Data sensitivity levels
     */
    private const SENSITIVITY_LEVELS = [
        'public' => [
            'encryption_required' => false,
            'key_rotation_days' => 0,
            'access_logging' => false
        ],
        'internal' => [
            'encryption_required' => false,
            'key_rotation_days' => 365,
            'access_logging' => true
        ],
        'confidential' => [
            'encryption_required' => true,
            'algorithm' => 'AES-256-CBC',
            'key_rotation_days' => 90,
            'access_logging' => true
        ],
        'restricted' => [
            'encryption_required' => true,
            'algorithm' => 'AES-256-GCM',
            'key_rotation_days' => 30,
            'access_logging' => true,
            'audit_required' => true
        ],
        'secret' => [
            'encryption_required' => true,
            'algorithm' => 'AES-256-GCM',
            'key_rotation_days' => 7,
            'access_logging' => true,
            'audit_required' => true,
            'multi_key_encryption' => true
        ]
    ];
    
    /**
     * Field-level encryption mappings
     */
    private array $fieldEncryptionMap = [
        // PII fields
        'email' => 'confidential',
        'phone' => 'confidential',
        'address' => 'confidential',
        'ssn' => 'secret',
        'credit_card' => 'secret',
        'bank_account' => 'secret',
        
        // Sensitive business data
        'salary' => 'restricted',
        'bonus' => 'restricted',
        'commission' => 'restricted',
        'password' => 'secret',
        'api_key' => 'secret',
        'token' => 'restricted',
        
        // Medical data
        'medical_record' => 'secret',
        'diagnosis' => 'restricted',
        'prescription' => 'restricted',
        
        // Financial data
        'revenue' => 'restricted',
        'profit' => 'restricted',
        'cost' => 'confidential',
    ];
    
    /**
     * Active encryption keys
     */
    private array $encryptionKeys = [];
    
    /**
     * Key derivation configuration
     */
    private array $keyDerivationConfig = [
        'algorithm' => 'PBKDF2',
        'hash' => 'sha256',
        'iterations' => 10000,
        'salt_length' => 16
    ];
    
    /**
     * Current encrypter instance
     */
    private ?Encrypter $encrypter = null;
    
    public function __construct()
    {
        $this->loadEncryptionKeys();
        $this->initializeEncrypter();
    }
    
    /**
     * Encrypt field data based on sensitivity level
     *
     * @param string $fieldName
     * @param mixed $data
     * @param array $options
     * @return array
     */
    public function encryptField(string $fieldName, $data, array $options = []): array
    {
        if ($data === null || $data === '') {
            return [
                'encrypted_data' => null,
                'encryption_metadata' => null
            ];
        }
        
        $sensitivityLevel = $this->getFieldSensitivity($fieldName, $options);
        $config = self::SENSITIVITY_LEVELS[$sensitivityLevel];
        
        if (!$config['encryption_required']) {
            return [
                'encrypted_data' => $data,
                'encryption_metadata' => [
                    'encrypted' => false,
                    'sensitivity' => $sensitivityLevel
                ]
            ];
        }
        
        try {
            $encryptionResult = $this->performEncryption($data, $sensitivityLevel, $options);
            
            $this->logEncryptionEvent($fieldName, $sensitivityLevel, 'encrypt');
            
            return [
                'encrypted_data' => $encryptionResult['ciphertext'],
                'encryption_metadata' => $encryptionResult['metadata']
            ];
            
        } catch (\Exception $e) {
            $this->logEncryptionError('encryption_failed', $fieldName, $e);
            throw new SecurityException('Encryption failed', [
                'field' => $fieldName,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Decrypt field data
     *
     * @param string $fieldName
     * @param string $encryptedData
     * @param array $metadata
     * @param array $options
     * @return mixed
     */
    public function decryptField(string $fieldName, string $encryptedData, array $metadata, array $options = []): mixed
    {
        if ($encryptedData === null || !($metadata['encrypted'] ?? false)) {
            return $encryptedData;
        }
        
        try {
            $decryptedData = $this->performDecryption($encryptedData, $metadata, $options);
            
            $this->logEncryptionEvent($fieldName, $metadata['sensitivity'] ?? 'unknown', 'decrypt');
            
            return $decryptedData;
            
        } catch (\Exception $e) {
            $this->logEncryptionError('decryption_failed', $fieldName, $e);
            throw new SecurityException('Decryption failed', [
                'field' => $fieldName,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Bulk encrypt array of data
     *
     * @param array $data
     * @param array $fieldMappings
     * @return array
     */
    public function bulkEncrypt(array $data, array $fieldMappings = []): array
    {
        $encryptedData = [];
        $encryptionMetadata = [];
        
        foreach ($data as $field => $value) {
            $result = $this->encryptField($field, $value, $fieldMappings);
            $encryptedData[$field] = $result['encrypted_data'];
            
            if ($result['encryption_metadata']) {
                $encryptionMetadata[$field] = $result['encryption_metadata'];
            }
        }
        
        return [
            'data' => $encryptedData,
            'metadata' => $encryptionMetadata
        ];
    }
    
    /**
     * Bulk decrypt array of data
     *
     * @param array $encryptedData
     * @param array $metadata
     * @return array
     */
    public function bulkDecrypt(array $encryptedData, array $metadata): array
    {
        $decryptedData = [];
        
        foreach ($encryptedData as $field => $value) {
            $fieldMetadata = $metadata[$field] ?? ['encrypted' => false];
            $decryptedData[$field] = $this->decryptField($field, $value, $fieldMetadata);
        }
        
        return $decryptedData;
    }
    
    /**
     * Generate new encryption key
     *
     * @param string $algorithm
     * @param array $options
     * @return array
     */
    public function generateEncryptionKey(string $algorithm = 'AES-256-CBC', array $options = []): array
    {
        if (!isset(self::SUPPORTED_ALGORITHMS[$algorithm])) {
            throw new SecurityException('Unsupported encryption algorithm', [
                'algorithm' => $algorithm,
                'supported' => array_keys(self::SUPPORTED_ALGORITHMS)
            ]);
        }
        
        $config = self::SUPPORTED_ALGORITHMS[$algorithm];
        $keyLength = $config['key_length'];
        
        // Generate cryptographically secure key
        $key = random_bytes($keyLength);
        $keyId = Str::uuid();
        
        $keyRecord = [
            'key_id' => $keyId,
            'algorithm' => $algorithm,
            'key_length' => $keyLength,
            'key_data' => base64_encode($key),
            'created_at' => now(),
            'status' => 'active',
            'rotation_due' => now()->addDays($options['rotation_days'] ?? 90),
            'usage_count' => 0,
            'max_usage' => $options['max_usage'] ?? 100000
        ];
        
        $this->storeEncryptionKey($keyRecord);
        $this->encryptionKeys[$keyId] = $keyRecord;
        
        $this->logKeyGeneration($keyRecord);
        
        return [
            'key_id' => $keyId,
            'algorithm' => $algorithm,
            'created_at' => $keyRecord['created_at']
        ];
    }
    
    /**
     * Rotate encryption keys
     *
     * @param array $options
     * @return array
     */
    public function rotateKeys(array $options = []): array
    {
        $rotationResults = [];
        $keysToRotate = $this->getKeysRequiringRotation();
        
        foreach ($keysToRotate as $oldKey) {
            try {
                // Generate new key
                $newKeyInfo = $this->generateEncryptionKey($oldKey['algorithm'], [
                    'rotation_days' => $this->getRotationPeriod($oldKey['sensitivity'] ?? 'confidential')
                ]);
                
                // Mark old key as rotated
                $this->markKeyAsRotated($oldKey['key_id'], $newKeyInfo['key_id']);
                
                // Re-encrypt data with new key (if needed)
                $reencryptionResult = $this->reencryptWithNewKey($oldKey['key_id'], $newKeyInfo['key_id']);
                
                $rotationResults[] = [
                    'old_key_id' => $oldKey['key_id'],
                    'new_key_id' => $newKeyInfo['key_id'],
                    'algorithm' => $oldKey['algorithm'],
                    'reencrypted_records' => $reencryptionResult['count'],
                    'rotation_completed_at' => now()
                ];
                
                $this->logKeyRotation($oldKey['key_id'], $newKeyInfo['key_id']);
                
            } catch (\Exception $e) {
                $this->logKeyRotationError($oldKey['key_id'], $e);
                $rotationResults[] = [
                    'old_key_id' => $oldKey['key_id'],
                    'error' => $e->getMessage(),
                    'rotation_failed_at' => now()
                ];
            }
        }
        
        return $rotationResults;
    }
    
    /**
     * Get encryption statistics
     *
     * @return array
     */
    public function getEncryptionStatistics(): array
    {
        return [
            'total_keys' => count($this->encryptionKeys),
            'active_keys' => count(array_filter($this->encryptionKeys, fn($k) => $k['status'] === 'active')),
            'keys_requiring_rotation' => count($this->getKeysRequiringRotation()),
            'encryption_usage' => $this->getEncryptionUsageStats(),
            'field_encryption_status' => $this->getFieldEncryptionStatus(),
            'algorithm_distribution' => $this->getAlgorithmDistribution(),
            'performance_metrics' => $this->getPerformanceMetrics()
        ];
    }
    
    /**
     * Database-level encryption support
     *
     * @param array $query
     * @param array $encryptedFields
     * @return array
     */
    public function prepareDatabaseQuery(array $query, array $encryptedFields): array
    {
        $preparedQuery = $query;
        
        foreach ($encryptedFields as $field) {
            if (isset($query['where'][$field])) {
                // Encrypt the search value
                $encryptResult = $this->encryptField($field, $query['where'][$field]);
                $preparedQuery['where'][$field] = $encryptResult['encrypted_data'];
            }
            
            if (isset($query['order_by']) && $query['order_by'] === $field) {
                // Cannot order by encrypted field directly - need special handling
                $this->logEncryptionWarning('ordering_encrypted_field', $field);
            }
        }
        
        return $preparedQuery;
    }
    
    /**
     * Perform encryption with specified algorithm
     *
     * @param mixed $data
     * @param string $sensitivityLevel
     * @param array $options
     * @return array
     */
    private function performEncryption($data, string $sensitivityLevel, array $options): array
    {
        $config = self::SENSITIVITY_LEVELS[$sensitivityLevel];
        $algorithm = $config['algorithm'] ?? 'AES-256-CBC';
        
        $serializedData = serialize($data);
        $key = $this->getActiveKeyForAlgorithm($algorithm);
        
        if (!$key) {
            // Generate new key if none exists
            $keyInfo = $this->generateEncryptionKey($algorithm);
            $key = $this->encryptionKeys[$keyInfo['key_id']];
        }
        
        switch ($algorithm) {
            case 'AES-256-CBC':
                return $this->encryptAesCbc($serializedData, $key);
            case 'AES-256-GCM':
                return $this->encryptAesGcm($serializedData, $key);
            case 'ChaCha20-Poly1305':
                return $this->encryptChaCha20($serializedData, $key);
            default:
                throw new SecurityException('Unsupported encryption algorithm', [
                    'algorithm' => $algorithm
                ]);
        }
    }
    
    /**
     * Perform decryption
     *
     * @param string $encryptedData
     * @param array $metadata
     * @param array $options
     * @return mixed
     */
    private function performDecryption(string $encryptedData, array $metadata, array $options): mixed
    {
        $algorithm = $metadata['algorithm'] ?? 'AES-256-CBC';
        $keyId = $metadata['key_id'] ?? null;
        
        if (!$keyId) {
            throw new SecurityException('Missing key ID in encryption metadata');
        }
        
        $key = $this->getKeyById($keyId);
        if (!$key) {
            throw new SecurityException('Encryption key not found', [
                'key_id' => $keyId
            ]);
        }
        
        switch ($algorithm) {
            case 'AES-256-CBC':
                $decryptedData = $this->decryptAesCbc($encryptedData, $key, $metadata);
                break;
            case 'AES-256-GCM':
                $decryptedData = $this->decryptAesGcm($encryptedData, $key, $metadata);
                break;
            case 'ChaCha20-Poly1305':
                $decryptedData = $this->decryptChaCha20($encryptedData, $key, $metadata);
                break;
            default:
                throw new SecurityException('Unsupported decryption algorithm', [
                    'algorithm' => $algorithm
                ]);
        }
        
        return unserialize($decryptedData);
    }
    
    /**
     * AES-256-CBC encryption
     *
     * @param string $data
     * @param array $key
     * @return array
     */
    private function encryptAesCbc(string $data, array $key): array
    {
        $iv = random_bytes(16);
        $keyData = base64_decode($key['key_data']);
        
        $ciphertext = openssl_encrypt($data, 'AES-256-CBC', $keyData, OPENSSL_RAW_DATA, $iv);
        
        if ($ciphertext === false) {
            throw new SecurityException('AES encryption failed');
        }
        
        $encryptedPackage = base64_encode($iv . $ciphertext);
        
        return [
            'ciphertext' => $encryptedPackage,
            'metadata' => [
                'encrypted' => true,
                'algorithm' => 'AES-256-CBC',
                'key_id' => $key['key_id'],
                'iv_length' => 16,
                'encrypted_at' => now()
            ]
        ];
    }
    
    /**
     * AES-256-CBC decryption
     *
     * @param string $encryptedData
     * @param array $key
     * @param array $metadata
     * @return string
     */
    private function decryptAesCbc(string $encryptedData, array $key, array $metadata): string
    {
        $package = base64_decode($encryptedData);
        $ivLength = $metadata['iv_length'] ?? 16;
        
        $iv = substr($package, 0, $ivLength);
        $ciphertext = substr($package, $ivLength);
        $keyData = base64_decode($key['key_data']);
        
        $plaintext = openssl_decrypt($ciphertext, 'AES-256-CBC', $keyData, OPENSSL_RAW_DATA, $iv);
        
        if ($plaintext === false) {
            throw new SecurityException('AES decryption failed');
        }
        
        return $plaintext;
    }
    
    /**
     * AES-256-GCM encryption
     *
     * @param string $data
     * @param array $key
     * @return array
     */
    private function encryptAesGcm(string $data, array $key): array
    {
        $iv = random_bytes(12);
        $keyData = base64_decode($key['key_data']);
        $tag = '';
        
        $ciphertext = openssl_encrypt($data, 'AES-256-GCM', $keyData, OPENSSL_RAW_DATA, $iv, $tag);
        
        if ($ciphertext === false) {
            throw new SecurityException('AES-GCM encryption failed');
        }
        
        $encryptedPackage = base64_encode($iv . $tag . $ciphertext);
        
        return [
            'ciphertext' => $encryptedPackage,
            'metadata' => [
                'encrypted' => true,
                'algorithm' => 'AES-256-GCM',
                'key_id' => $key['key_id'],
                'iv_length' => 12,
                'tag_length' => 16,
                'encrypted_at' => now()
            ]
        ];
    }
    
    /**
     * AES-256-GCM decryption
     *
     * @param string $encryptedData
     * @param array $key
     * @param array $metadata
     * @return string
     */
    private function decryptAesGcm(string $encryptedData, array $key, array $metadata): string
    {
        $package = base64_decode($encryptedData);
        $ivLength = $metadata['iv_length'] ?? 12;
        $tagLength = $metadata['tag_length'] ?? 16;
        
        $iv = substr($package, 0, $ivLength);
        $tag = substr($package, $ivLength, $tagLength);
        $ciphertext = substr($package, $ivLength + $tagLength);
        $keyData = base64_decode($key['key_data']);
        
        $plaintext = openssl_decrypt($ciphertext, 'AES-256-GCM', $keyData, OPENSSL_RAW_DATA, $iv, $tag);
        
        if ($plaintext === false) {
            throw new SecurityException('AES-GCM decryption failed');
        }
        
        return $plaintext;
    }
    
    /**
     * Get field sensitivity level
     *
     * @param string $fieldName
     * @param array $options
     * @return string
     */
    private function getFieldSensitivity(string $fieldName, array $options): string
    {
        // Check options first
        if (isset($options['sensitivity'])) {
            return $options['sensitivity'];
        }
        
        // Check field mapping
        if (isset($this->fieldEncryptionMap[$fieldName])) {
            return $this->fieldEncryptionMap[$fieldName];
        }
        
        // Check pattern matching
        $patterns = [
            '/password/' => 'secret',
            '/email/' => 'confidential',
            '/phone/' => 'confidential',
            '/address/' => 'confidential',
            '/salary|wage|income/' => 'restricted',
            '/ssn|social/' => 'secret',
            '/credit|card/' => 'secret',
        ];
        
        $lowerFieldName = strtolower($fieldName);
        foreach ($patterns as $pattern => $sensitivity) {
            if (preg_match($pattern, $lowerFieldName)) {
                return $sensitivity;
            }
        }
        
        return 'internal'; // Default
    }
    
    // Helper methods for key management and utilities
    
    private function loadEncryptionKeys(): void
    {
        // Load keys from secure storage
        $this->encryptionKeys = Cache::get('encryption_keys', []);
    }
    
    private function initializeEncrypter(): void
    {
        $key = Config::get('app.key');
        $this->encrypter = new Encrypter(base64_decode(substr($key, 7)), 'AES-256-CBC');
    }
    
    private function getActiveKeyForAlgorithm(string $algorithm): ?array
    {
        foreach ($this->encryptionKeys as $key) {
            if ($key['algorithm'] === $algorithm && $key['status'] === 'active') {
                return $key;
            }
        }
        return null;
    }
    
    private function getKeyById(string $keyId): ?array
    {
        return $this->encryptionKeys[$keyId] ?? null;
    }
    
    // Placeholder methods for implementation
    private function encryptChaCha20(string $data, array $key): array { return []; }
    private function decryptChaCha20(string $data, array $key, array $metadata): string { return ''; }
    private function storeEncryptionKey(array $keyRecord): void { }
    private function getKeysRequiringRotation(): array { return []; }
    private function markKeyAsRotated(string $oldKeyId, string $newKeyId): void { }
    private function reencryptWithNewKey(string $oldKeyId, string $newKeyId): array { return ['count' => 0]; }
    private function getRotationPeriod(string $sensitivity): int { return 90; }
    private function getEncryptionUsageStats(): array { return []; }
    private function getFieldEncryptionStatus(): array { return []; }
    private function getAlgorithmDistribution(): array { return []; }
    private function getPerformanceMetrics(): array { return []; }
    private function logEncryptionEvent(string $field, string $level, string $action): void { }
    private function logEncryptionError(string $type, string $field, \Exception $e): void { }
    private function logEncryptionWarning(string $type, string $field): void { }
    private function logKeyGeneration(array $keyRecord): void { }
    private function logKeyRotation(string $oldKeyId, string $newKeyId): void { }
    private function logKeyRotationError(string $keyId, \Exception $e): void { }
}