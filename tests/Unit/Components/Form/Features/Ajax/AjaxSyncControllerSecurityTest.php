<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Features\Ajax;

use Canvastack\Canvastack\Components\Form\Features\Ajax\QueryEncryption;
use Canvastack\Canvastack\Http\Controllers\AjaxSyncController;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Security tests for AjaxSyncController.
 *
 * Tests Requirements:
 * - 2.17: SQL injection prevention
 * - 14.2: Security measures for Ajax sync
 *
 * Test Coverage:
 * - SQL injection attempts are blocked
 * - Non-SELECT queries are rejected
 * - Malicious input handling
 * - Suspicious pattern detection
 * - Query validation
 * - Logging of security violations
 */
class AjaxSyncControllerSecurityTest extends TestCase
{
    protected AjaxSyncController $controller;

    protected QueryEncryption $encryption;

    protected function setUp(): void
    {
        parent::setUp();

        $this->encryption = new QueryEncryption(app(Encrypter::class));
        $this->controller = new AjaxSyncController($this->encryption);

        // Clear cache before each test
        Cache::flush();

        // Clear logs
        Log::spy();
    }

    /**
     * Test that INSERT queries are rejected.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_rejects_insert_queries(): void
    {
        $maliciousQuery = "INSERT INTO users (name, email) VALUES ('hacker', 'hack@evil.com')";

        $request = $this->createAjaxRequest($maliciousQuery);
        $response = $this->controller->handle($request);

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Only SELECT queries are allowed', $data['message']);

        // Verify logging
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Ajax sync: Non-SELECT query attempted', \Mockery::type('array'));
    }

    /**
     * Test that UPDATE queries are rejected.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_rejects_update_queries(): void
    {
        $maliciousQuery = "UPDATE users SET role = 'admin' WHERE id = 1";

        $request = $this->createAjaxRequest($maliciousQuery);
        $response = $this->controller->handle($request);

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Only SELECT queries are allowed', $data['message']);
    }

    /**
     * Test that DELETE queries are rejected.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_rejects_delete_queries(): void
    {
        $maliciousQuery = 'DELETE FROM users WHERE id = 1';

        $request = $this->createAjaxRequest($maliciousQuery);
        $response = $this->controller->handle($request);

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
    }

    /**
     * Test that DROP queries are rejected.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_rejects_drop_queries(): void
    {
        $maliciousQuery = 'DROP TABLE users';

        $request = $this->createAjaxRequest($maliciousQuery);
        $response = $this->controller->handle($request);

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
    }

    /**
     * Test that CREATE queries are rejected.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_rejects_create_queries(): void
    {
        $maliciousQuery = 'CREATE TABLE malicious (id INT)';

        $request = $this->createAjaxRequest($maliciousQuery);
        $response = $this->controller->handle($request);

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test that ALTER queries are rejected.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_rejects_alter_queries(): void
    {
        $maliciousQuery = 'ALTER TABLE users ADD COLUMN backdoor VARCHAR(255)';

        $request = $this->createAjaxRequest($maliciousQuery);
        $response = $this->controller->handle($request);

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test that TRUNCATE queries are rejected.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_rejects_truncate_queries(): void
    {
        $maliciousQuery = 'TRUNCATE TABLE users';

        $request = $this->createAjaxRequest($maliciousQuery);
        $response = $this->controller->handle($request);

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test that EXEC/EXECUTE queries are rejected.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_rejects_exec_queries(): void
    {
        $maliciousQuery = "EXEC sp_executesql N'SELECT * FROM users'";

        $request = $this->createAjaxRequest($maliciousQuery);
        $response = $this->controller->handle($request);

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test that queries with multiple statements are rejected.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_rejects_multiple_statement_injection(): void
    {
        $maliciousQuery = 'SELECT id, name FROM cities WHERE province_id = ?; DROP TABLE users';

        $request = $this->createAjaxRequest($maliciousQuery);
        $response = $this->controller->handle($request);

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        // Query is rejected either as non-SELECT or suspicious pattern
        $this->assertTrue(
            str_contains($data['message'], 'suspicious patterns') ||
            str_contains($data['message'], 'Only SELECT queries are allowed')
        );
    }

    /**
     * Test that UNION SELECT injection is rejected.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_rejects_union_select_injection(): void
    {
        $maliciousQuery = 'SELECT id, name FROM cities WHERE province_id = ? UNION SELECT id, password FROM users';

        $request = $this->createAjaxRequest($maliciousQuery);
        $response = $this->controller->handle($request);

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('suspicious patterns', $data['message']);
    }

    /**
     * Test that SQL comments are rejected.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_rejects_queries_with_sql_comments(): void
    {
        $maliciousQueries = [
            'SELECT id, name FROM cities WHERE province_id = ? -- comment',
            'SELECT id, name FROM cities WHERE province_id = ? # comment',
            'SELECT id, name FROM cities WHERE province_id = ? /* comment */',
        ];

        foreach ($maliciousQueries as $query) {
            $request = $this->createAjaxRequest($query);
            $response = $this->controller->handle($request);

            $this->assertEquals(403, $response->getStatusCode(), "Failed to reject query: {$query}");
        }
    }

    /**
     * Test that OR injection patterns are rejected.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_rejects_or_injection_patterns(): void
    {
        $maliciousQuery = 'SELECT id, name FROM cities WHERE province_id = ? OR 1=1 OR 2=2';

        $request = $this->createAjaxRequest($maliciousQuery);
        $response = $this->controller->handle($request);

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test that AND injection patterns are rejected.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_rejects_and_injection_patterns(): void
    {
        $maliciousQuery = 'SELECT id, name FROM cities WHERE province_id = ? AND 1=1 AND 2=2';

        $request = $this->createAjaxRequest($maliciousQuery);
        $response = $this->controller->handle($request);

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test that hex value injection is rejected.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_rejects_hex_value_injection(): void
    {
        $maliciousQuery = 'SELECT id, name FROM cities WHERE province_id = 0x61646D696E';

        $request = $this->createAjaxRequest($maliciousQuery);
        $response = $this->controller->handle($request);

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test that GRANT queries are rejected.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_rejects_grant_queries(): void
    {
        $maliciousQuery = "GRANT ALL PRIVILEGES ON *.* TO 'hacker'@'%'";

        $request = $this->createAjaxRequest($maliciousQuery);
        $response = $this->controller->handle($request);

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test that LOAD DATA queries are rejected.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_rejects_load_data_queries(): void
    {
        $maliciousQuery = "LOAD DATA INFILE '/etc/passwd' INTO TABLE users";

        $request = $this->createAjaxRequest($maliciousQuery);
        $response = $this->controller->handle($request);

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test that queries with OUTFILE are rejected.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_rejects_outfile_queries(): void
    {
        $maliciousQuery = "SELECT * FROM users INTO OUTFILE '/tmp/users.txt'";

        $request = $this->createAjaxRequest($maliciousQuery);
        $response = $this->controller->handle($request);

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test that invalid encrypted data is rejected.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_rejects_invalid_encrypted_data(): void
    {
        $request = Request::create('/ajax/sync', 'POST', [
            'relationship' => [
                'source' => 'province_id',
                'target' => 'city_id',
                'values' => 'invalid_encrypted_data',
                'labels' => 'invalid_encrypted_data',
                'query' => 'invalid_encrypted_data',
            ],
            'sourceValue' => 1,
        ]);

        $request->headers->set('Accept', 'application/json');
        $response = $this->controller->handle($request);

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Invalid encrypted data', $data['message']);

        // Verify error logging
        Log::shouldHaveReceived('error')
            ->once()
            ->with('Ajax sync: Decryption failed', \Mockery::type('array'));
    }

    /**
     * Test that missing required fields are rejected.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_rejects_missing_required_fields(): void
    {
        $request = Request::create('/ajax/sync', 'POST', [
            'relationship' => [
                'source' => 'province_id',
                // Missing target, values, labels, query
            ],
            'sourceValue' => 1,
        ]);

        $request->headers->set('Accept', 'application/json');
        $response = $this->controller->handle($request);

        $this->assertEquals(422, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Validation failed', $data['message']);
    }

    /**
     * Test that legitimate SELECT queries are allowed.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_allows_legitimate_select_queries(): void
    {
        DB::shouldReceive('select')
            ->once()
            ->with('SELECT id, name FROM cities WHERE province_id = ?', [1])
            ->andReturn([
                (object) ['id' => 1, 'name' => 'Jakarta'],
                (object) ['id' => 2, 'name' => 'Bandung'],
            ]);

        $legitimateQuery = 'SELECT id, name FROM cities WHERE province_id = ?';

        $request = $this->createAjaxRequest($legitimateQuery);
        $response = $this->controller->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('options', $data['data']);
        $this->assertCount(2, $data['data']['options']);
    }

    /**
     * Test that SELECT queries with WHERE clause are allowed.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_allows_select_with_where_clause(): void
    {
        DB::shouldReceive('select')
            ->once()
            ->andReturn([
                (object) ['id' => 1, 'name' => 'Jakarta'],
            ]);

        $query = 'SELECT id, name FROM cities WHERE province_id = ? AND active = 1';

        $request = $this->createAjaxRequest($query);
        $response = $this->controller->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test that SELECT queries with JOIN are allowed.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_allows_select_with_join(): void
    {
        DB::shouldReceive('select')
            ->once()
            ->andReturn([
                (object) ['id' => 1, 'name' => 'Jakarta'],
            ]);

        $query = 'SELECT c.id, c.name FROM cities c JOIN provinces p ON c.province_id = p.id WHERE p.id = ?';

        $request = $this->createAjaxRequest($query);
        $response = $this->controller->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test that SELECT queries with ORDER BY are allowed.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_allows_select_with_order_by(): void
    {
        DB::shouldReceive('select')
            ->once()
            ->andReturn([
                (object) ['id' => 1, 'name' => 'Jakarta'],
            ]);

        $query = 'SELECT id, name FROM cities WHERE province_id = ? ORDER BY name ASC';

        $request = $this->createAjaxRequest($query);
        $response = $this->controller->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test that results are limited to MAX_OPTIONS.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_limits_results_to_max_options(): void
    {
        // Create 1500 results (exceeds MAX_OPTIONS of 1000)
        $results = [];
        for ($i = 1; $i <= 1500; $i++) {
            $results[] = (object) ['id' => $i, 'name' => "City {$i}"];
        }

        DB::shouldReceive('select')
            ->once()
            ->andReturn($results);

        $query = 'SELECT id, name FROM cities WHERE province_id = ?';

        $request = $this->createAjaxRequest($query);
        $response = $this->controller->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(1000, $data['data']['options'], 'Results should be limited to 1000');
    }

    /**
     * Test that security violations are logged with IP address.
     *
     * @test
     * @group security
     * @group ajax-sync
     */
    public function it_logs_security_violations_with_ip(): void
    {
        $maliciousQuery = 'DROP TABLE users';

        $request = $this->createAjaxRequest($maliciousQuery);
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        $response = $this->controller->handle($request);

        $this->assertEquals(403, $response->getStatusCode());

        // Verify IP is logged
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Ajax sync: Non-SELECT query attempted', \Mockery::on(function ($data) {
                return isset($data['ip']) && $data['ip'] === '192.168.1.100';
            }));
    }

    /**
     * Helper method to create an Ajax request with encrypted parameters.
     *
     * @param string $query SQL query to encrypt
     * @return Request
     */
    protected function createAjaxRequest(string $query): Request
    {
        $request = Request::create('/ajax/sync', 'POST', [
            'relationship' => [
                'source' => 'province_id',
                'target' => 'city_id',
                'values' => $this->encryption->encrypt('id'),
                'labels' => $this->encryption->encrypt('name'),
                'query' => $this->encryption->encrypt($query),
            ],
            'sourceValue' => 1,
        ]);

        $request->headers->set('Accept', 'application/json');

        return $request;
    }
}
