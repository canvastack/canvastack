<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Security;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * ContentSecurityPolicyManager
 * 
 * Dynamic CSP header generation and violation reporting for Canvastack Tables
 * Implements nonce-based script execution, dynamic policies, violation monitoring
 * 
 * @package Canvastack\Table\Security
 * @version 2.0
 * @author Security Hardening Team
 */
class ContentSecurityPolicyManager
{
    /**
     * CSP directive templates
     */
    private const CSP_DIRECTIVES = [
        'default-src' => ["'self'"],
        'script-src' => ["'self'", "'unsafe-inline'", "'unsafe-eval'"],
        'style-src' => ["'self'", "'unsafe-inline'"],
        'img-src' => ["'self'", "data:", "blob:"],
        'font-src' => ["'self'", "data:"],
        'connect-src' => ["'self'"],
        'media-src' => ["'self'"],
        'object-src' => ["'none'"],
        'child-src' => ["'self'"],
        'worker-src' => ["'self'"],
        'manifest-src' => ["'self'"],
        'base-uri' => ["'self'"],
        'form-action' => ["'self'"],
        'frame-ancestors' => ["'none'"],
        'report-uri' => ['/api/csp-violation-report'],
        'report-to' => ['csp-endpoint']
    ];
    
    /**
     * Security levels configuration
     */
    private const SECURITY_LEVELS = [
        'strict' => [
            'script-src' => ["'self'"],
            'style-src' => ["'self'"],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
            'upgrade-insecure-requests' => true
        ],
        'moderate' => [
            'script-src' => ["'self'", "'unsafe-inline'"],
            'style-src' => ["'self'", "'unsafe-inline'"],
            'img-src' => ["'self'", "data:", "https:"],
            'font-src' => ["'self'", "data:", "https:"],
            'connect-src' => ["'self'", "https:"]
        ],
        'permissive' => [
            'script-src' => ["'self'", "'unsafe-inline'", "'unsafe-eval'"],
            'style-src' => ["'self'", "'unsafe-inline'"],
            'img-src' => ["'self'", "data:", "blob:", "*"],
            'font-src' => ["'self'", "data:", "*"],
            'connect-src' => ["'self'", "*"]
        ]
    ];
    
    /**
     * CDN and external domains whitelist
     */
    private array $trustedDomains = [
        'script-src' => [
            'https://cdnjs.cloudflare.com',
            'https://cdn.jsdelivr.net',
            'https://unpkg.com',
            'https://code.jquery.com'
        ],
        'style-src' => [
            'https://cdnjs.cloudflare.com',
            'https://cdn.jsdelivr.net',
            'https://fonts.googleapis.com'
        ],
        'font-src' => [
            'https://fonts.gstatic.com',
            'https://cdnjs.cloudflare.com'
        ],
        'img-src' => [
            'https://via.placeholder.com',
            'https://picsum.photos'
        ]
    ];
    
    /**
     * Current nonce for script/style tags
     */
    private ?string $currentNonce = null;
    
    /**
     * CSP violation reports storage
     */
    private array $violationReports = [];
    
    /**
     * Current security level
     */
    private string $securityLevel = 'moderate';
    
    public function __construct()
    {
        $this->securityLevel = Config::get('canvastack.security.csp.level', 'moderate');
        $this->loadTrustedDomains();
    }
    
    /**
     * Generate CSP header for current request
     *
     * @param Request $request
     * @param array $additionalDirectives
     * @return string
     */
    public function generateCspHeader(Request $request, array $additionalDirectives = []): string
    {
        $this->generateNonce();
        
        $directives = $this->buildDirectives($request, $additionalDirectives);
        $cspHeader = $this->formatCspHeader($directives);
        
        $this->logCspGeneration($request, $cspHeader);
        
        return $cspHeader;
    }
    
    /**
     * Generate nonce for current request
     *
     * @return string
     */
    public function generateNonce(): string
    {
        if ($this->currentNonce === null) {
            $this->currentNonce = base64_encode(random_bytes(16));
        }
        
        return $this->currentNonce;
    }
    
    /**
     * Get current nonce value
     *
     * @return string|null
     */
    public function getCurrentNonce(): ?string
    {
        return $this->currentNonce;
    }
    
    /**
     * Add nonce to script tag
     *
     * @param string $scriptContent
     * @param array $attributes
     * @return string
     */
    public function addNonceToScript(string $scriptContent, array $attributes = []): string
    {
        $nonce = $this->generateNonce();
        $attributeString = '';
        
        foreach ($attributes as $key => $value) {
            $attributeString .= sprintf(' %s="%s"', $key, htmlspecialchars($value));
        }
        
        return sprintf(
            '<script nonce="%s"%s>%s</script>',
            $nonce,
            $attributeString,
            $scriptContent
        );
    }
    
    /**
     * Add nonce to style tag
     *
     * @param string $styleContent
     * @param array $attributes
     * @return string
     */
    public function addNonceToStyle(string $styleContent, array $attributes = []): string
    {
        $nonce = $this->generateNonce();
        $attributeString = '';
        
        foreach ($attributes as $key => $value) {
            $attributeString .= sprintf(' %s="%s"', $key, htmlspecialchars($value));
        }
        
        return sprintf(
            '<style nonce="%s"%s>%s</style>',
            $nonce,
            $attributeString,
            $styleContent
        );
    }
    
    /**
     * Process CSP violation report
     *
     * @param Request $request
     * @return void
     */
    public function processCspViolation(Request $request): void
    {
        $violationData = $request->all();
        
        $report = [
            'timestamp' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->url(),
            'violation' => $violationData,
            'severity' => $this->analyzeSeverity($violationData),
            'potential_attack' => $this->isPotentialAttack($violationData)
        ];
        
        $this->storeViolationReport($report);
        $this->logViolation($report);
        
        if ($report['potential_attack']) {
            $this->alertPotentialAttack($report);
        }
    }
    
    /**
     * Get CSP violation statistics
     *
     * @param int $hours
     * @return array
     */
    public function getViolationStatistics(int $hours = 24): array
    {
        $reports = $this->getViolationReports($hours);
        
        return [
            'total_violations' => count($reports),
            'by_directive' => $this->groupBy($reports, 'violation.csp-report.violated-directive'),
            'by_source' => $this->groupBy($reports, 'violation.csp-report.source-file'),
            'by_severity' => $this->groupBy($reports, 'severity'),
            'potential_attacks' => count(array_filter($reports, fn($r) => $r['potential_attack'])),
            'top_violating_ips' => $this->getTopViolatingIps($reports),
            'violation_trends' => $this->calculateTrends($reports)
        ];
    }
    
    /**
     * Update CSP policy dynamically
     *
     * @param array $newDirectives
     * @param bool $temporary
     * @return void
     */
    public function updateCspPolicy(array $newDirectives, bool $temporary = false): void
    {
        $currentPolicy = $this->getCurrentPolicy();
        $updatedPolicy = array_merge($currentPolicy, $newDirectives);
        
        if ($temporary) {
            Cache::put('csp_temp_policy', $updatedPolicy, now()->addHours(1));
        } else {
            Config::set('canvastack.security.csp.custom_directives', $updatedPolicy);
        }
        
        $this->logPolicyUpdate($newDirectives, $temporary);
    }
    
    /**
     * Generate reporting endpoint configuration
     *
     * @return array
     */
    public function generateReportingConfig(): array
    {
        return [
            'group' => 'csp-endpoint',
            'max_age' => 86400, // 24 hours
            'endpoints' => [
                [
                    'url' => url('/api/csp-violation-report'),
                    'priority' => 1,
                    'weight' => 1
                ]
            ]
        ];
    }
    
    /**
     * Build CSP directives based on request context
     *
     * @param Request $request
     * @param array $additionalDirectives
     * @return array
     */
    private function buildDirectives(Request $request, array $additionalDirectives = []): array
    {
        $baseDirectives = $this->getBaseDirectives();
        $contextDirectives = $this->getContextualDirectives($request);
        $customDirectives = $this->getCustomDirectives();
        
        // Add nonce to script and style directives
        $nonceValue = "'nonce-{$this->getCurrentNonce()}'";
        
        if (isset($baseDirectives['script-src'])) {
            $baseDirectives['script-src'][] = $nonceValue;
        }
        
        if (isset($baseDirectives['style-src'])) {
            $baseDirectives['style-src'][] = $nonceValue;
        }
        
        return array_merge(
            $baseDirectives,
            $contextDirectives,
            $customDirectives,
            $additionalDirectives
        );
    }
    
    /**
     * Get base directives based on security level
     *
     * @return array
     */
    private function getBaseDirectives(): array
    {
        $base = self::CSP_DIRECTIVES;
        $levelConfig = self::SECURITY_LEVELS[$this->securityLevel] ?? [];
        
        foreach ($levelConfig as $directive => $values) {
            if ($directive === 'upgrade-insecure-requests' && $values === true) {
                $base['upgrade-insecure-requests'] = [];
                continue;
            }
            
            $base[$directive] = $values;
        }
        
        // Add trusted domains
        foreach ($this->trustedDomains as $directive => $domains) {
            if (isset($base[$directive])) {
                $base[$directive] = array_merge($base[$directive], $domains);
            }
        }
        
        return $base;
    }
    
    /**
     * Get contextual directives based on request
     *
     * @param Request $request
     * @return array
     */
    private function getContextualDirectives(Request $request): array
    {
        $directives = [];
        
        // Add specific directives for datatables requests
        if ($this->isDatatablesRequest($request)) {
            $directives['connect-src'] = array_merge(
                $directives['connect-src'] ?? [],
                [$request->getSchemeAndHttpHost()]
            );
        }
        
        // Add AJAX-specific directives
        if ($request->ajax()) {
            $directives['connect-src'] = array_merge(
                $directives['connect-src'] ?? [],
                [$request->getSchemeAndHttpHost()]
            );
        }
        
        return $directives;
    }
    
    /**
     * Get custom directives from configuration
     *
     * @return array
     */
    private function getCustomDirectives(): array
    {
        return array_merge(
            Config::get('canvastack.security.csp.custom_directives', []),
            Cache::get('csp_temp_policy', [])
        );
    }
    
    /**
     * Format CSP header string
     *
     * @param array $directives
     * @return string
     */
    private function formatCspHeader(array $directives): string
    {
        $headerParts = [];
        
        foreach ($directives as $directive => $values) {
            if (empty($values)) {
                $headerParts[] = $directive;
            } else {
                $headerParts[] = $directive . ' ' . implode(' ', array_unique($values));
            }
        }
        
        return implode('; ', $headerParts);
    }
    
    /**
     * Check if request is for datatables
     *
     * @param Request $request
     * @return bool
     */
    private function isDatatablesRequest(Request $request): bool
    {
        return $request->hasHeader('X-Datatables-Request') ||
               $request->has('draw') ||
               str_contains($request->url(), '/datatables/') ||
               str_contains($request->url(), '/canvastack/');
    }
    
    /**
     * Analyze violation severity
     *
     * @param array $violationData
     * @return string
     */
    private function analyzeSeverity(array $violationData): string
    {
        $cspReport = $violationData['csp-report'] ?? [];
        $violatedDirective = $cspReport['violated-directive'] ?? '';
        $blockedUri = $cspReport['blocked-uri'] ?? '';
        
        // High severity violations
        if (in_array($violatedDirective, ['script-src', 'object-src', 'base-uri'])) {
            return 'high';
        }
        
        // Medium severity violations
        if (in_array($violatedDirective, ['style-src', 'img-src', 'font-src'])) {
            return 'medium';
        }
        
        // Low severity violations
        return 'low';
    }
    
    /**
     * Check if violation indicates potential attack
     *
     * @param array $violationData
     * @return bool
     */
    private function isPotentialAttack(array $violationData): bool
    {
        $cspReport = $violationData['csp-report'] ?? [];
        $blockedUri = $cspReport['blocked-uri'] ?? '';
        $violatedDirective = $cspReport['violated-directive'] ?? '';
        
        $suspiciousPatterns = [
            '/javascript:/',
            '/data:text\/html/',
            '/eval\(/',
            '/document\.write/',
            '/<script.*?>/',
            '/on(load|error|click)=/',
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $blockedUri)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Store violation report
     *
     * @param array $report
     */
    private function storeViolationReport(array $report): void
    {
        $this->violationReports[] = $report;
        
        // Store in cache for statistics
        $cacheKey = 'csp_violations:' . date('Y-m-d-H');
        $existingReports = Cache::get($cacheKey, []);
        $existingReports[] = $report;
        Cache::put($cacheKey, $existingReports, now()->addHours(25));
        
        // Store critical violations in database
        if ($report['severity'] === 'high' || $report['potential_attack']) {
            $this->storeCriticalViolation($report);
        }
    }
    
    /**
     * Log violation report
     *
     * @param array $report
     */
    private function logViolation(array $report): void
    {
        $logLevel = $report['potential_attack'] ? 'critical' : 
                   ($report['severity'] === 'high' ? 'error' : 'warning');
        
        Log::channel('security')->{$logLevel}('CSP Violation detected', $report);
    }
    
    /**
     * Alert for potential attack
     *
     * @param array $report
     */
    private function alertPotentialAttack(array $report): void
    {
        // Send immediate alert for potential XSS attempts
        Log::channel('security-alerts')->critical('CSP Violation - Potential Attack', [
            'alert_type' => 'csp_violation_attack',
            'severity' => 'critical',
            'ip_address' => $report['ip_address'],
            'violation_details' => $report['violation'],
            'recommended_action' => 'Block IP and investigate'
        ]);
    }
    
    /**
     * Load trusted domains from configuration
     */
    private function loadTrustedDomains(): void
    {
        $configDomains = Config::get('canvastack.security.csp.trusted_domains', []);
        
        foreach ($configDomains as $directive => $domains) {
            if (isset($this->trustedDomains[$directive])) {
                $this->trustedDomains[$directive] = array_merge(
                    $this->trustedDomains[$directive],
                    $domains
                );
            } else {
                $this->trustedDomains[$directive] = $domains;
            }
        }
    }
    
    /**
     * Log CSP generation
     *
     * @param Request $request
     * @param string $cspHeader
     */
    private function logCspGeneration(Request $request, string $cspHeader): void
    {
        Log::channel('security')->debug('CSP header generated', [
            'url' => $request->url(),
            'csp_header' => $cspHeader,
            'nonce' => $this->currentNonce,
            'security_level' => $this->securityLevel
        ]);
    }
    
    /**
     * Log policy update
     *
     * @param array $newDirectives
     * @param bool $temporary
     */
    private function logPolicyUpdate(array $newDirectives, bool $temporary): void
    {
        Log::channel('security')->info('CSP policy updated', [
            'new_directives' => $newDirectives,
            'temporary' => $temporary,
            'updated_at' => now()
        ]);
    }
    
    // Placeholder methods for additional functionality
    private function getCurrentPolicy(): array { return []; }
    private function getViolationReports(int $hours): array { return []; }
    private function groupBy(array $reports, string $key): array { return []; }
    private function getTopViolatingIps(array $reports): array { return []; }
    private function calculateTrends(array $reports): array { return []; }
    private function storeCriticalViolation(array $report): void { }
}