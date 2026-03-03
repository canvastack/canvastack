# Testing Guide - CanvaStack

## Setup

### 1. Install Dependencies
```bash
cd packages/canvastack/canvastack
composer install
```

### 2. Run Tests

#### Run All Tests
```bash
# Windows PowerShell
vendor\bin\phpunit

# Atau pakai composer script
composer test
```

#### Run Specific Test File
```bash
# Test Controller
vendor\bin\phpunit tests\Unit\Http\ControllerTest.php

# Test Model
vendor\bin\phpunit tests\Unit\Models\BaseModelTest.php

# Test Core
vendor\bin\phpunit tests\Unit\Core\ContainerTest.php
vendor\bin\phpunit tests\Unit\Core\ApplicationTest.php
```

#### Run dengan Filter
```bash
# Run test method tertentu
vendor\bin\phpunit --filter test_success_response

# Run test class tertentu
vendor\bin\phpunit --filter ControllerTest
```

#### Run dengan Coverage
```bash
# Generate HTML coverage report
vendor\bin\phpunit --coverage-html coverage

# Generate text coverage
vendor\bin\phpunit --coverage-text

# Generate Clover XML (untuk CI/CD)
vendor\bin\phpunit --coverage-clover coverage.xml
```

### 3. Code Quality Tools

#### Laravel Pint (Code Formatting)
```bash
# Format semua file
vendor\bin\pint

# Format specific directory
vendor\bin\pint src/Http

# Dry run (preview changes)
vendor\bin\pint --test
```

#### PHPStan (Static Analysis)
```bash
# Analyze semua file
vendor\bin\phpstan analyse

# Analyze specific directory
vendor\bin\phpstan analyse src/Http

# Generate baseline
vendor\bin\phpstan analyse --generate-baseline
```

## Test Structure

```
tests/
├── Unit/                    # Unit tests
│   ├── Core/               # Core functionality tests
│   ├── Http/               # Controller tests
│   ├── Models/             # Model tests
│   ├── Repositories/       # Repository tests
│   └── Support/            # Helper tests
├── Feature/                # Feature tests
│   ├── Components/         # Component integration tests
│   ├── Auth/               # Authentication tests
│   └── RBAC/               # Authorization tests
├── Performance/            # Performance tests
│   ├── Benchmarks/         # Benchmark tests
│   └── Load/               # Load tests
└── TestCase.php            # Base test case
```

## Writing Tests

### Unit Test Example

```php
<?php

namespace Canvastack\Canvastack\Tests\Unit\Http;

use Canvastack\Canvastack\Tests\TestCase;
use Canvastack\Canvastack\Http\Controller;

class ControllerTest extends TestCase
{
    public function test_success_response_returns_json(): void
    {
        $controller = new TestController();
        $response = $controller->testSuccess(['id' => 1], 'Success');
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Success', $data['message']);
    }
}
```

### Feature Test Example

```php
<?php

namespace Canvastack\Canvastack\Tests\Feature;

use Canvastack\Canvastack\Tests\TestCase;

class FormBuilderTest extends TestCase
{
    public function test_form_can_be_rendered(): void
    {
        $response = $this->get('/admin/users/create');
        
        $response->assertStatus(200);
        $response->assertSee('Create User');
        $response->assertSee('name="name"');
        $response->assertSee('name="email"');
    }
}
```

### Performance Test Example

```php
<?php

namespace Canvastack\Canvastack\Tests\Performance;

use Canvastack\Canvastack\Tests\TestCase;

class TablePerformanceTest extends TestCase
{
    public function test_table_loads_1000_rows_under_500ms(): void
    {
        $start = microtime(true);
        
        $users = User::paginate(1000);
        $table = TableBuilder::make($users)->render();
        
        $duration = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(500, $duration, 
            "Table took {$duration}ms, expected < 500ms"
        );
    }
}
```

## Test Coverage Goals

| Component | Target Coverage |
|-----------|----------------|
| Core | 100% |
| Controllers | 90% |
| Models | 90% |
| Repositories | 95% |
| Services | 90% |
| Components | 85% |
| Overall | 80%+ |

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
          extensions: mbstring, pdo, pdo_mysql
          coverage: xdebug
      
      - name: Install Dependencies
        run: composer install
      
      - name: Run Tests
        run: vendor/bin/phpunit --coverage-clover coverage.xml
      
      - name: Upload Coverage
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage.xml
```

## Troubleshooting

### Issue: "Class not found"
**Solution**: Run `composer dump-autoload`

### Issue: "Database connection failed"
**Solution**: Tests use SQLite in-memory database by default. Check `TestCase.php` configuration.

### Issue: "Namespace mismatch"
**Solution**: Ensure all classes use `Canvastack\Canvastack\` namespace prefix.

### Issue: "PHPUnit not found"
**Solution**: Run `composer install` to install dependencies.

## Best Practices

1. **Test Naming**: Use descriptive names that explain what is being tested
   - Good: `test_user_can_create_post_with_valid_data()`
   - Bad: `test_create()`

2. **Arrange-Act-Assert**: Structure tests clearly
   ```php
   public function test_example(): void
   {
       // Arrange
       $user = User::factory()->create();
       
       // Act
       $response = $this->actingAs($user)->get('/dashboard');
       
       // Assert
       $response->assertStatus(200);
   }
   ```

3. **One Assertion Per Test**: Focus each test on one behavior
   - Exception: Related assertions (status code + content)

4. **Use Factories**: Create test data with factories
   ```php
   $user = User::factory()->create(['status' => 'active']);
   ```

5. **Mock External Services**: Don't hit real APIs in tests
   ```php
   Http::fake([
       'api.example.com/*' => Http::response(['data' => 'test'], 200)
   ]);
   ```

## Next Steps

1. Fix namespace issues in existing classes
2. Run all tests to ensure they pass
3. Add more test coverage for new features
4. Setup CI/CD pipeline
5. Monitor coverage metrics

---

**Last Updated**: 2026-02-24  
**Status**: In Progress
