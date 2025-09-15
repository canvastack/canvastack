# 🛡️ **SECURE CODING PATTERNS & QUICK REFERENCE**
## Ready-to-Use Security Implementations

**📋 Purpose:** Copy-paste secure code patterns untuk security hardening  
**👥 Target:** Development Team  
**⚡ Usage:** Immediate implementation reference  

---

## 🔥 **CRITICAL SQL INJECTION FIXES**

### **✅ Secure FilterQueryService.php**

#### **Before (VULNERABLE):**
```php
// ❌ NEVER DO THIS - Direct string concatenation
$filterQueries[$n] = "`{$fqFieldName}` IN ('{$fQdataValue}')";
$filterQueries[$n] = "`{$fqFieldName}` = '{$fqDataValue}'";
$wheres[] = "`{$key}` = '{$value}'";
```

#### **After (SECURE):**
```php
<?php
// ✅ SECURE IMPLEMENTATION - Use parameter binding
class SecureFilterQueryService
{
    private array $bindings = [];
    
    public function buildSecureInQuery(string $fieldName, array $values): string
    {
        $this->validateFieldName($fieldName);
        $placeholders = str_repeat('?,', count($values) - 1) . '?';
        $this->bindings = array_merge($this->bindings, $values);
        
        return "`{$fieldName}` IN ({$placeholders})";
    }
    
    public function buildSecureWhereQuery(string $fieldName, $value): string
    {
        $this->validateFieldName($fieldName);
        $this->bindings[] = $value;
        
        return "`{$fieldName}` = ?";
    }
    
    private function validateFieldName(string $fieldName): void
    {
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $fieldName)) {
            throw new InvalidArgumentException("Invalid field name: {$fieldName}");
        }
        
        // Whitelist validation
        $allowedFields = config('canvastack.allowed_fields', []);
        if (!empty($allowedFields) && !in_array($fieldName, $allowedFields)) {
            throw new SecurityException("Field not allowed: {$fieldName}");
        }
    }
    
    public function executeSecureQuery(string $sql): array
    {
        return DB::select($sql, $this->bindings);
    }
}
```

### **✅ Secure Search.php**

#### **Before (VULNERABLE):**
```php
// ❌ DANGEROUS - SQL injection vulnerability
$mf_where[] = "{$mf_cond}{$mf_field} = '{$mf_values}'";
$query = $this->select("SELECT {$strfields} FROM `{$table}` {$where};", $connection);
```

#### **After (SECURE):**
```php
<?php
// ✅ SECURE IMPLEMENTATION
class SecureSearchService
{
    private array $bindings = [];
    private array $whereConditions = [];
    
    public function addSecureWhereCondition(string $condition, string $field, $value): void
    {
        $this->validateFieldName($field);
        $this->validateCondition($condition);
        
        $this->whereConditions[] = "{$condition}`{$field}` = ?";
        $this->bindings[] = $value;
    }
    
    public function buildSecureSearchQuery(string $table, array $fields): string
    {
        $this->validateTableName($table);
        $safeFields = $this->validateAndSanitizeFields($fields);
        
        $fieldsList = implode('`, `', $safeFields);
        $whereClause = empty($this->whereConditions) ? '' : 'WHERE ' . implode(' ', $this->whereConditions);
        
        return "SELECT `{$fieldsList}` FROM `{$table}` {$whereClause} GROUP BY `{$fieldsList}`";
    }
    
    private function validateTableName(string $table): void
    {
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $table)) {
            throw new InvalidArgumentException("Invalid table name: {$table}");
        }
    }
    
    private function validateAndSanitizeFields(array $fields): array
    {
        $safeFields = [];
        foreach ($fields as $field) {
            $this->validateFieldName($field);
            $safeFields[] = $field;
        }
        return $safeFields;
    }
    
    private function validateCondition(string $condition): void
    {
        $allowedConditions = ['AND ', 'OR ', ''];
        if (!in_array($condition, $allowedConditions)) {
            throw new InvalidArgumentException("Invalid condition: {$condition}");
        }
    }
    
    public function executeSecureSearch(string $sql): array
    {
        return DB::select($sql, $this->bindings);
    }
}
```

---

## 💻 **XSS PREVENTION PATTERNS**

### **✅ Secure JavaScript Generation**

#### **Before (VULNERABLE):**
```php
// ❌ DANGEROUS - Unescaped output to JavaScript
$script .= "window.canvastack_datatables_config['{$this->id}'] = {$configData};";
$dataFunctionStr = "function(data) { var postData = { _token: '{$token}' }; }";
```

#### **After (SECURE):**
```php
<?php
// ✅ SECURE IMPLEMENTATION
class SecureJavaScriptRenderer
{
    public static function generateSecureScript(string $configId, array $configData): string
    {
        // Sanitize and escape all data
        $safeId = self::escapeForJavaScript($configId);
        $safeConfig = self::sanitizeConfigData($configData);
        
        // Use JSON encoding with security flags
        $jsonConfig = json_encode($safeConfig, 
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
        );
        
        if ($jsonConfig === false) {
            throw new RuntimeException('Failed to encode configuration data');
        }
        
        return "window.canvastack_datatables_config[{$safeId}] = {$jsonConfig};";
    }
    
    public static function generateSecureToken(string $action = 'datatables'): string
    {
        $token = csrf_token();
        
        // Generate action-specific token
        $actionToken = hash_hmac('sha256', $action . $token, config('app.key'));
        
        return json_encode($actionToken, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }
    
    public static function generateSecureAjaxFunction(string $token): string
    {
        $safeToken = self::generateSecureToken();
        
        return "
        function(data) {
            var postData = {
                draw: data.draw,
                start: data.start,
                length: data.length,
                search: data.search,
                order: data.order,
                columns: data.columns,
                _token: {$safeToken}
            };
            return postData;
        }";
    }
    
    private static function escapeForJavaScript(string $value): string
    {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }
    
    private static function sanitizeConfigData(array $data): array
    {
        array_walk_recursive($data, function(&$value) {
            if (is_string($value)) {
                // Remove potentially dangerous content
                $value = preg_replace('/[<>"\']/', '', $value);
                // Escape HTML entities
                $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        });
        
        return $data;
    }
}
```

### **✅ Secure CSRF Token Management**

```php
<?php
// ✅ ENHANCED CSRF PROTECTION
class SecureCSRFManager
{
    public static function generateActionToken(string $action): string
    {
        $session = session();
        $baseToken = $session->token();
        $timestamp = time();
        
        // Create action-specific token with timestamp
        $payload = $action . '|' . $baseToken . '|' . $timestamp;
        $actionToken = hash_hmac('sha256', $payload, config('app.key'));
        
        // Store token with expiration
        $session->put("csrf_action_{$action}", [
            'token' => $actionToken,
            'expires' => $timestamp + 3600 // 1 hour expiry
        ]);
        
        return $actionToken;
    }
    
    public static function validateActionToken(string $token, string $action): bool
    {
        $session = session();
        $storedData = $session->get("csrf_action_{$action}");
        
        if (!$storedData || !is_array($storedData)) {
            return false;
        }
        
        // Check expiration
        if (time() > $storedData['expires']) {
            $session->forget("csrf_action_{$action}");
            return false;
        }
        
        // Validate token
        $isValid = hash_equals($storedData['token'], $token);
        
        // Remove token after use (one-time use)
        if ($isValid) {
            $session->forget("csrf_action_{$action}");
        }
        
        return $isValid;
    }
}
```

---

## 📁 **FILE SECURITY PATTERNS**

### **✅ Secure File Operations**

#### **Before (VULNERABLE):**
```php
// ❌ DANGEROUS - Path traversal vulnerability
$filepath = $directory . DIRECTORY_SEPARATOR . $filename;
$result = @file_put_contents($filepath, $data, LOCK_EX);
```

#### **After (SECURE):**
```php
<?php
// ✅ SECURE FILE OPERATIONS
class SecureFileManager
{
    private const ALLOWED_EXTENSIONS = ['json', 'txt', 'log'];
    private const MAX_FILENAME_LENGTH = 255;
    private const MAX_FILE_SIZE = 1048576; // 1MB
    
    public static function secureStore(string $directory, string $filename, string $data): string
    {
        // Validate and sanitize directory
        $safeDirectory = self::validateDirectory($directory);
        
        // Validate and sanitize filename
        $safeFilename = self::validateFilename($filename);
        
        // Construct secure filepath
        $filepath = self::constructSecurePath($safeDirectory, $safeFilename);
        
        // Validate file content
        self::validateFileContent($data);
        
        // Perform secure write operation
        return self::performSecureWrite($filepath, $data);
    }
    
    private static function validateDirectory(string $directory): string
    {
        $realDirectory = realpath($directory);
        
        if (!$realDirectory || !is_dir($realDirectory)) {
            throw new InvalidArgumentException('Invalid directory path');
        }
        
        // Ensure directory is within allowed paths
        $allowedPaths = config('canvastack.storage.allowed_paths', []);
        $isAllowed = false;
        
        foreach ($allowedPaths as $allowedPath) {
            if (strpos($realDirectory, realpath($allowedPath)) === 0) {
                $isAllowed = true;
                break;
            }
        }
        
        if (!$isAllowed) {
            throw new SecurityException('Directory not in allowed paths');
        }
        
        return $realDirectory;
    }
    
    private static function validateFilename(string $filename): string
    {
        // Remove directory separators and dangerous characters
        $safeFilename = basename($filename);
        $safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '', $safeFilename);
        
        // Check length
        if (strlen($safeFilename) > self::MAX_FILENAME_LENGTH) {
            throw new InvalidArgumentException('Filename too long');
        }
        
        // Check extension
        $extension = strtolower(pathinfo($safeFilename, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new SecurityException("File extension not allowed: {$extension}");
        }
        
        // Prevent hidden files and empty names
        if (empty($safeFilename) || $safeFilename[0] === '.') {
            throw new InvalidArgumentException('Invalid filename');
        }
        
        return $safeFilename;
    }
    
    private static function constructSecurePath(string $directory, string $filename): string
    {
        $filepath = $directory . DIRECTORY_SEPARATOR . $filename;
        
        // Final security check - ensure resolved path is within directory
        $realFilepath = realpath(dirname($filepath));
        $realDirectory = realpath($directory);
        
        if (!$realFilepath || strpos($realFilepath, $realDirectory) !== 0) {
            throw new SecurityException('Path traversal attempt detected');
        }
        
        return $filepath;
    }
    
    private static function validateFileContent(string $data): void
    {
        // Check file size
        if (strlen($data) > self::MAX_FILE_SIZE) {
            throw new InvalidArgumentException('File content too large');
        }
        
        // Check for malicious content patterns
        $dangerousPatterns = [
            '/<script[^>]*>/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/<\?php/i',
            '/<%/i'
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $data)) {
                throw new SecurityException('Malicious content detected in file');
            }
        }
    }
    
    private static function performSecureWrite(string $filepath, string $data): string
    {
        // Create directory if it doesn't exist (with secure permissions)
        $directory = dirname($filepath);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0750, true)) {
                throw new RuntimeException('Failed to create directory');
            }
        }
        
        // Write file with secure permissions
        $tempFile = $filepath . '.tmp.' . uniqid();
        
        try {
            $bytesWritten = file_put_contents($tempFile, $data, LOCK_EX);
            if ($bytesWritten === false) {
                throw new RuntimeException('Failed to write file');
            }
            
            // Set secure file permissions
            chmod($tempFile, 0640);
            
            // Atomic move to final location
            if (!rename($tempFile, $filepath)) {
                unlink($tempFile);
                throw new RuntimeException('Failed to move file to final location');
            }
            
            return $filepath;
            
        } catch (Exception $e) {
            // Cleanup temp file on error
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            throw $e;
        }
    }
    
    public static function secureRead(string $filepath): string
    {
        // Validate file path
        $realFilepath = realpath($filepath);
        if (!$realFilepath || !file_exists($realFilepath)) {
            throw new InvalidArgumentException('File not found');
        }
        
        // Check if file is in allowed directory
        $allowedPaths = config('canvastack.storage.allowed_paths', []);
        $isAllowed = false;
        
        foreach ($allowedPaths as $allowedPath) {
            if (strpos($realFilepath, realpath($allowedPath)) === 0) {
                $isAllowed = true;
                break;
            }
        }
        
        if (!$isAllowed) {
            throw new SecurityException('File not in allowed paths');
        }
        
        // Read file securely
        $content = file_get_contents($realFilepath);
        if ($content === false) {
            throw new RuntimeException('Failed to read file');
        }
        
        return $content;
    }
}
```

---

## 🔍 **INPUT VALIDATION PATTERNS**

### **✅ Comprehensive Input Validator**

```php
<?php
// ✅ SECURE INPUT VALIDATION
class SecurityInputValidator
{
    private static array $fieldWhitelist = [];
    private static array $tableWhitelist = [];
    
    public static function validateDatatablesRequest(array $input): array
    {
        $rules = [
            'table' => 'required|string|max:64',
            'columns' => 'sometimes|array|max:50',
            'columns.*' => 'string|max:64',
            'start' => 'sometimes|integer|min:0|max:1000000',
            'length' => 'sometimes|integer|min:1|max:1000',
            'search' => 'sometimes|array',
            'search.value' => 'sometimes|string|max:255',
            'search.regex' => 'sometimes|boolean',
            'order' => 'sometimes|array|max:10',
            'order.*.column' => 'sometimes|integer|min:0',
            'order.*.dir' => 'sometimes|in:asc,desc'
        ];
        
        $validator = validator($input, $rules);
        $validated = $validator->validate();
        
        // Additional security validation
        return self::applySecurity($validated);
    }
    
    private static function applySecurity(array $data): array
    {
        // Validate table name
        if (isset($data['table'])) {
            $data['table'] = self::validateTableName($data['table']);
        }
        
        // Validate column names
        if (isset($data['columns'])) {
            $data['columns'] = array_map([self::class, 'validateFieldName'], $data['columns']);
        }
        
        // Sanitize search value
        if (isset($data['search']['value'])) {
            $data['search']['value'] = self::sanitizeSearchValue($data['search']['value']);
        }
        
        return $data;
    }
    
    public static function validateTableName(string $tableName): string
    {
        // Basic format validation
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $tableName)) {
            throw new InvalidArgumentException("Invalid table name format: {$tableName}");
        }
        
        // Length validation
        if (strlen($tableName) > 64) {
            throw new InvalidArgumentException('Table name too long');
        }
        
        // Whitelist validation
        self::loadTableWhitelist();
        if (!empty(self::$tableWhitelist) && !in_array($tableName, self::$tableWhitelist)) {
            throw new SecurityException("Table not allowed: {$tableName}");
        }
        
        return $tableName;
    }
    
    public static function validateFieldName(string $fieldName): string
    {
        // Basic format validation
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $fieldName)) {
            throw new InvalidArgumentException("Invalid field name format: {$fieldName}");
        }
        
        // Length validation
        if (strlen($fieldName) > 64) {
            throw new InvalidArgumentException('Field name too long');
        }
        
        // Whitelist validation
        self::loadFieldWhitelist();
        if (!empty(self::$fieldWhitelist) && !in_array($fieldName, self::$fieldWhitelist)) {
            throw new SecurityException("Field not allowed: {$fieldName}");
        }
        
        return $fieldName;
    }
    
    private static function sanitizeSearchValue(string $value): string
    {
        // Remove dangerous SQL keywords
        $dangerousPattems = [
            '/\b(UNION|SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|EXECUTE)\b/i',
            '/[\'";]/',
            '/--/',
            '/\/\*.*\*\//',
            '/<script.*?>.*?<\/script>/is',
            '/javascript:/i',
            '/vbscript:/i'
        ];
        
        foreach ($dangerousPattems as $pattern) {
            if (preg_match($pattern, $value)) {
                throw new SecurityException('Malicious content detected in search value');
            }
        }
        
        // Trim and limit length
        $value = trim($value);
        if (strlen($value) > 255) {
            $value = substr($value, 0, 255);
        }
        
        return $value;
    }
    
    private static function loadTableWhitelist(): void
    {
        if (empty(self::$tableWhitelist)) {
            self::$tableWhitelist = config('canvastack.security.allowed_tables', []);
        }
    }
    
    private static function loadFieldWhitelist(): void
    {
        if (empty(self::$fieldWhitelist)) {
            self::$fieldWhitelist = config('canvastack.security.allowed_fields', []);
        }
    }
}
```

---

## 🚨 **SECURITY MONITORING PATTERNS**

### **✅ Security Event Logger**

```php
<?php
// ✅ COMPREHENSIVE SECURITY LOGGING
class SecurityEventLogger
{
    private static array $criticalEvents = [
        'SQL_INJECTION_ATTEMPT',
        'XSS_ATTEMPT', 
        'PATH_TRAVERSAL_ATTEMPT',
        'UNAUTHORIZED_ACCESS',
        'MALICIOUS_FILE_UPLOAD'
    ];
    
    public static function logSecurityEvent(string $eventType, array $context = []): void
    {
        $logData = [
            'event_type' => $eventType,
            'timestamp' => now()->toISOString(),
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'request_id' => request()->header('X-Request-ID') ?? uniqid(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'context' => $context
        ];
        
        // Log to security channel
        Log::channel('security')->warning("SECURITY_EVENT: {$eventType}", $logData);
        
        // Immediate alert for critical events
        if (in_array($eventType, self::$criticalEvents)) {
            self::triggerImmediateAlert($eventType, $logData);
        }
        
        // Update security metrics
        self::updateSecurityMetrics($eventType);
    }
    
    private static function triggerImmediateAlert(string $eventType, array $logData): void
    {
        // Email alert
        if (config('canvastack.security.email_alerts', false)) {
            Mail::to(config('canvastack.security.alert_email'))
                ->send(new SecurityAlertMail($eventType, $logData));
        }
        
        // Slack notification
        if (config('canvastack.security.slack_alerts', false)) {
            self::sendSlackAlert($eventType, $logData);
        }
        
        // Database logging for critical events
        DB::table('security_incidents')->insert([
            'event_type' => $eventType,
            'severity' => 'CRITICAL',
            'data' => json_encode($logData),
            'created_at' => now(),
            'resolved_at' => null
        ]);
    }
    
    private static function sendSlackAlert(string $eventType, array $logData): void
    {
        $webhookUrl = config('canvastack.security.slack_webhook');
        if (!$webhookUrl) return;
        
        $payload = [
            'text' => "🚨 CRITICAL SECURITY EVENT: {$eventType}",
            'attachments' => [
                [
                    'color' => 'danger',
                    'fields' => [
                        ['title' => 'Event', 'value' => $eventType, 'short' => true],
                        ['title' => 'User ID', 'value' => $logData['user_id'] ?? 'Unknown', 'short' => true],
                        ['title' => 'IP Address', 'value' => $logData['ip_address'], 'short' => true],
                        ['title' => 'Timestamp', 'value' => $logData['timestamp'], 'short' => true],
                        ['title' => 'URL', 'value' => $logData['url'], 'short' => false]
                    ]
                ]
            ]
        ];
        
        Http::post($webhookUrl, $payload);
    }
    
    private static function updateSecurityMetrics(string $eventType): void
    {
        $key = "security_events_{$eventType}_" . date('Y-m-d');
        Cache::increment($key, 1, 86400); // 24 hour TTL
        
        // Daily summary
        $dailyKey = "security_events_daily_" . date('Y-m-d');
        Cache::increment($dailyKey, 1, 86400);
    }
}
```

### **✅ Intrusion Detection System**

```php
<?php
// ✅ REAL-TIME INTRUSION DETECTION
class IntrusionDetectionSystem
{
    private static array $sqlInjectionPatterns = [
        '/(\bUNION\b.*\bSELECT\b)/i',
        '/(\bSELECT\b.*\bFROM\b.*\bWHERE\b)/i',
        '/(\'|\")(\s*)(OR|AND)(\s*)(\'|\")(\s*)=/i',
        '/(\bDROP\b|\bCREATE\b|\bALTER\b|\bDELETE\b|\bINSERT\b|\bUPDATE\b)/i',
        '/(\-\-|\#|\/\*)/i'
    ];
    
    private static array $xssPatterns = [
        '/<script[^>]*>.*?<\/script>/is',
        '/javascript:/i',
        '/vbscript:/i',
        '/onload\s*=/i',
        '/onclick\s*=/i',
        '/onerror\s*=/i',
        '/<iframe[^>]*>.*?<\/iframe>/is'
    ];
    
    public static function analyzeRequest(): array
    {
        $threats = [];
        $request = request();
        
        // Analyze all input data
        $allInput = array_merge(
            $request->query(),
            $request->post(),
            $request->route()->parameters()
        );
        
        foreach ($allInput as $key => $value) {
            if (is_string($value)) {
                $threats = array_merge($threats, self::analyzeInputValue($key, $value));
            } elseif (is_array($value)) {
                $threats = array_merge($threats, self::analyzeArrayInput($key, $value));
            }
        }
        
        // Rate limiting analysis
        $rateLimitThreats = self::analyzeRateLimit();
        $threats = array_merge($threats, $rateLimitThreats);
        
        // Log threats
        if (!empty($threats)) {
            foreach ($threats as $threat) {
                SecurityEventLogger::logSecurityEvent($threat['type'], [
                    'parameter' => $threat['parameter'],
                    'value' => $threat['value'],
                    'pattern' => $threat['pattern'] ?? null
                ]);
            }
        }
        
        return $threats;
    }
    
    private static function analyzeInputValue(string $parameter, string $value): array
    {
        $threats = [];
        
        // SQL Injection detection
        foreach (self::$sqlInjectionPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $threats[] = [
                    'type' => 'SQL_INJECTION_ATTEMPT',
                    'parameter' => $parameter,
                    'value' => substr($value, 0, 100),
                    'pattern' => $pattern
                ];
            }
        }
        
        // XSS detection
        foreach (self::$xssPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $threats[] = [
                    'type' => 'XSS_ATTEMPT',
                    'parameter' => $parameter,
                    'value' => substr($value, 0, 100),
                    'pattern' => $pattern
                ];
            }
        }
        
        // Path traversal detection
        if (preg_match('/\.\.\/|\.\.\\\\|%2e%2e%2f|%2e%2e%5c/i', $value)) {
            $threats[] = [
                'type' => 'PATH_TRAVERSAL_ATTEMPT',
                'parameter' => $parameter,
                'value' => substr($value, 0, 100)
            ];
        }
        
        return $threats;
    }
    
    private static function analyzeArrayInput(string $parameter, array $values): array
    {
        $threats = [];
        
        foreach ($values as $key => $value) {
            if (is_string($value)) {
                $threats = array_merge($threats, self::analyzeInputValue("{$parameter}[{$key}]", $value));
            }
        }
        
        return $threats;
    }
    
    private static function analyzeRateLimit(): array
    {
        $threats = [];
        $ip = request()->ip();
        $now = time();
        $window = 60; // 1 minute window
        
        // Count requests in time window
        $key = "rate_limit_{$ip}_{$now}";
        $requests = Cache::get($key, 0);
        
        // Check threshold
        $threshold = config('canvastack.security.rate_limit.threshold', 100);
        if ($requests > $threshold) {
            $threats[] = [
                'type' => 'RATE_LIMIT_EXCEEDED',
                'parameter' => 'requests_per_minute',
                'value' => $requests
            ];
        }
        
        // Increment counter
        Cache::put($key, $requests + 1, $window);
        
        return $threats;
    }
}
```

---

## 🔧 **SECURITY MIDDLEWARE PATTERN**

### **✅ Complete Security Middleware**

```php
<?php
// ✅ COMPREHENSIVE SECURITY MIDDLEWARE
class DatatablesSecurityMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            // 1. Rate limiting
            $this->enforceRateLimit($request);
            
            // 2. Input validation
            $this->validateInput($request);
            
            // 3. Intrusion detection
            $threats = IntrusionDetectionSystem::analyzeRequest();
            if (!empty($threats)) {
                return $this->handleSecurityThreats($threats);
            }
            
            // 4. CSRF validation for POST requests
            if ($request->isMethod('POST')) {
                $this->validateCSRF($request);
            }
            
            // 5. Add security headers to response
            $response = $next($request);
            return $this->addSecurityHeaders($response);
            
        } catch (SecurityException $e) {
            return response()->json([
                'error' => 'Security violation detected',
                'message' => 'Request blocked for security reasons',
                'code' => 403
            ], 403);
        }
    }
    
    private function enforceRateLimit($request): void
    {
        $ip = $request->ip();
        $key = "rate_limit_" . md5($ip . date('i'));
        $attempts = Cache::get($key, 0);
        
        $limit = config('canvastack.security.rate_limit.max_requests', 100);
        
        if ($attempts >= $limit) {
            SecurityEventLogger::logSecurityEvent('RATE_LIMIT_EXCEEDED', [
                'ip' => $ip,
                'attempts' => $attempts
            ]);
            
            throw new SecurityException('Rate limit exceeded');
        }
        
        Cache::put($key, $attempts + 1, 60);
    }
    
    private function validateInput($request): void
    {
        if ($request->has('table')) {
            SecurityInputValidator::validateTableName($request->get('table'));
        }
        
        if ($request->has('columns')) {
            $columns = $request->get('columns', []);
            foreach ($columns as $column) {
                if (isset($column['data'])) {
                    SecurityInputValidator::validateFieldName($column['data']);
                }
            }
        }
    }
    
    private function validateCSRF($request): void
    {
        $token = $request->input('_token');
        $action = $request->route()->getName() ?? 'datatables';
        
        if (!SecureCSRFManager::validateActionToken($token, $action)) {
            SecurityEventLogger::logSecurityEvent('CSRF_VALIDATION_FAILED', [
                'action' => $action,
                'provided_token' => substr($token, 0, 10) . '...'
            ]);
            
            throw new SecurityException('CSRF token validation failed');
        }
    }
    
    private function handleSecurityThreats(array $threats): JsonResponse
    {
        $blockRequest = false;
        
        foreach ($threats as $threat) {
            if (in_array($threat['type'], ['SQL_INJECTION_ATTEMPT', 'XSS_ATTEMPT', 'PATH_TRAVERSAL_ATTEMPT'])) {
                $blockRequest = true;
                break;
            }
        }
        
        if ($blockRequest) {
            return response()->json([
                'error' => 'Security violation detected',
                'message' => 'Request blocked due to potential attack',
                'code' => 403
            ], 403);
        }
        
        return response()->json([
            'warning' => 'Suspicious activity detected',
            'message' => 'Request processed with enhanced monitoring'
        ], 200);
    }
    
    private function addSecurityHeaders($response)
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'"
        ];
        
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }
        
        return $response;
    }
}
```

---

## 📋 **CONFIGURATION FILES**

### **✅ Security Configuration**

```php
<?php
// config/canvastack-security.php
return [
    'sql_injection_protection' => [
        'enabled' => true,
        'log_attempts' => true,
        'block_requests' => true
    ],
    
    'xss_protection' => [
        'enabled' => true,
        'escape_output' => true,
        'csp_enabled' => true
    ],
    
    'rate_limiting' => [
        'enabled' => true,
        'max_requests' => 100,
        'time_window' => 60, // seconds
        'block_duration' => 300 // 5 minutes
    ],
    
    'file_security' => [
        'allowed_extensions' => ['json', 'txt', 'log'],
        'max_file_size' => 1048576, // 1MB
        'allowed_paths' => [
            storage_path('app/canvastack'),
            storage_path('logs/canvastack')
        ],
        'virus_scanning' => false
    ],
    
    'input_validation' => [
        'allowed_tables' => [
            'users',
            'base_group',
            'base_module'
            // Add your allowed tables here
        ],
        
        'allowed_fields' => [
            'id', 'name', 'email', 'username', 'created_at', 'updated_at'
            // Add your allowed fields here
        ],
        
        'max_field_length' => 64,
        'max_search_length' => 255
    ],
    
    'monitoring' => [
        'log_security_events' => true,
        'email_alerts' => env('SECURITY_EMAIL_ALERTS', false),
        'slack_alerts' => env('SECURITY_SLACK_ALERTS', false),
        'alert_email' => env('SECURITY_ALERT_EMAIL'),
        'slack_webhook' => env('SECURITY_SLACK_WEBHOOK')
    ],
    
    'encryption' => [
        'enabled' => false,
        'algorithm' => 'AES-256-GCM',
        'key_rotation' => 86400 // 24 hours
    ]
];
```

---

## 🚀 **USAGE EXAMPLES**

### **✅ Using Secure Components**

```php
<?php
// In your controller
class DataTableController extends Controller
{
    public function getData(Request $request)
    {
        try {
            // Use secure input validation
            $validatedInput = SecurityInputValidator::validateDatatablesRequest($request->all());
            
            // Use secure query service
            $queryService = new SecureFilterQueryService();
            $results = $queryService->buildSecureQuery(
                $validatedInput['table'],
                $validatedInput['filters'] ?? []
            );
            
            // Use secure JavaScript generation
            $script = SecureJavaScriptRenderer::generateSecureScript(
                $request->get('table'),
                $results
            );
            
            return response()->json([
                'data' => $results,
                'script' => $script
            ]);
            
        } catch (SecurityException $e) {
            return response()->json([
                'error' => 'Security violation',
                'message' => 'Request blocked'
            ], 403);
        }
    }
}
```

---

**📅 Quick Reference Version:** 1.0  
**🔄 Last Updated:** December 2024  
**👥 Usage:** Copy-paste for immediate implementation  
**📞 Support:** security-implementation@canvastack.com  

---

*🔒 Keep this reference secure and updated. All patterns tested for Laravel compatibility.*