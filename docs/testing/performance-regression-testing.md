# Performance Regression Testing

## Overview

The Performance Regression Testing system monitors performance metrics across releases to detect performance degradations early. It establishes baseline metrics and automatically fails builds when performance regresses beyond acceptable thresholds.

**Status**: Active  
**Version**: 1.0.0  
**Last Updated**: 2026-02-28

---

## Key Features

- **Baseline Metrics**: Established performance baselines for all operations
- **Automated Monitoring**: CI/CD integration for continuous monitoring
- **Regression Detection**: Automatic detection of performance degradations
- **Threshold-Based Alerts**: Configurable regression thresholds
- **Detailed Reports**: Comprehensive performance comparison reports
- **Historical Tracking**: Track performance trends over time

---

## Baseline Metrics (v1.0.0)

### Response Time Baselines

| Operation | Baseline | Threshold (20% regression) | Target |
|-----------|----------|---------------------------|--------|
| Row-level check | 25ms | 30ms | < 50ms |
| Column-level check | 4ms | 4.8ms | < 10ms |
| JSON attribute check | 8ms | 9.6ms | < 15ms |
| Conditional check | 18ms | 21.6ms | < 30ms |
| Get accessible columns | 4ms | 4.8ms | < 10ms |
| Get accessible JSON paths | 8ms | 9.6ms | < 15ms |
| Multiple rules check | 30ms | 36ms | < 50ms |
| Cache warming | 50ms | 60ms | < 100ms |

### Cache Performance Baselines

| Metric | Baseline | Threshold (20% regression) | Target |
|--------|----------|---------------------------|--------|
| Cache hit rate | 91% | 72.8% | > 80% |

### Resource Usage Baselines

| Metric | Baseline | Threshold (20% regression) | Target |
|--------|----------|---------------------------|--------|
| Memory usage (1000 checks) | 6MB | 7.2MB | < 10MB |

---

## Running Performance Regression Tests

### Manual Execution

```bash
# Run all performance regression tests
cd packages/canvastack/canvastack
./vendor/bin/phpunit --testsuite=Performance --filter=PerformanceRegressionTest

# Run specific regression test
./vendor/bin/phpunit tests/Performance/Auth/RBAC/PerformanceRegressionTest.php

# Run with verbose output
./vendor/bin/phpunit --testsuite=Performance --filter=PerformanceRegressionTest --verbose
```

### Using Performance Monitor Script

```bash
# Run tests and show results
php scripts/performance-monitor.php

# Save current metrics as baseline
php scripts/performance-monitor.php --save-baseline

# Compare with baseline and output markdown
php scripts/performance-monitor.php --format=markdown --output=report.md

# Compare with custom threshold
php scripts/performance-monitor.php --threshold=15

# Compare two metrics files
php scripts/performance-monitor.php --compare=metrics-old.json
```

---

## CI/CD Integration

### GitHub Actions Workflow

The performance regression tests are automatically run on:
- Every push to `main` or `develop` branches
- Every pull request
- Daily scheduled runs at 2 AM UTC

**Workflow File**: `.github/workflows/performance-tests.yml`

### Workflow Steps

1. **Setup Environment**: PHP, Redis, dependencies
2. **Run Regression Tests**: Execute performance regression test suite
3. **Run All Performance Tests**: Execute complete performance test suite
4. **Generate Report**: Create performance report in markdown format
5. **Upload Artifacts**: Save performance reports as artifacts
6. **Comment on PR**: Post performance results as PR comment
7. **Fail on Regression**: Fail build if regressions detected

### Performance Comparison

For pull requests, the workflow also:
1. Runs performance tests on the PR branch
2. Checks out the base branch
3. Runs performance tests on the base branch
4. Compares the results
5. Posts comparison as PR comment

---

## Understanding Test Results

### Test Output Format

```
Row-level check: 24.50ms (baseline: 25.00ms, ↓0.5% ✓ IMPROVED)
Column-level check: 4.20ms (baseline: 4.00ms, ↑5.0% ✓ OK)
JSON attribute check: 8.50ms (baseline: 8.00ms, ↑6.3% ✓ OK)
Conditional check: 18.00ms (baseline: 18.00ms, ↑0.0% ✓ OK)
Cache hit rate: 92.00% (baseline: 91.00%, ↑1.1% ✓ IMPROVED)
Memory usage: 5.80MB (baseline: 6.00MB, ↓3.3% ✓ IMPROVED)
```

### Status Indicators

- **✓ IMPROVED**: Performance improved compared to baseline
- **✓ OK**: Performance within acceptable range (< 20% regression)
- **✗ REGRESSION**: Performance degraded beyond threshold (≥ 20% regression)

### Symbols

- **↓**: Improvement (lower is better for time/memory)
- **↑**: Regression (higher is worse for time/memory)

---

## Regression Thresholds

### Default Threshold

**20% regression** is the maximum allowed degradation from baseline.

### Rationale

- Allows for minor variations due to system load
- Catches significant performance issues
- Balances sensitivity with false positives

### Customizing Thresholds

You can customize the regression threshold:

```bash
# Use 15% threshold
php scripts/performance-monitor.php --threshold=15

# Use 25% threshold
php scripts/performance-monitor.php --threshold=25
```

---

## Baseline Management

### Creating a Baseline

When establishing a new baseline (e.g., after major optimizations):

```bash
# Run tests and save as baseline
php scripts/performance-monitor.php --save-baseline
```

This creates `.performance-baseline.json` with current metrics.

### Updating Baselines

Baselines should be updated when:
- Major performance optimizations are implemented
- System requirements change
- Infrastructure is upgraded
- After verifying improvements are legitimate

### Baseline File Format

```json
{
  "timestamp": "2026-02-28 10:30:00",
  "tests": {
    "test_row_level_check_no_regression": {
      "status": "passed",
      "time": 0.025,
      "memory": 6291456
    },
    "test_column_level_check_no_regression": {
      "status": "passed",
      "time": 0.004,
      "memory": 4194304
    }
  }
}
```

---

## Troubleshooting Regressions

### When a Regression is Detected

1. **Verify the Regression**
   - Run tests multiple times to confirm
   - Check if system load affected results
   - Review recent code changes

2. **Identify the Cause**
   - Review git history for recent changes
   - Profile the affected operation
   - Check for new dependencies or queries

3. **Fix the Issue**
   - Optimize the problematic code
   - Add caching if appropriate
   - Reduce database queries
   - Optimize algorithms

4. **Verify the Fix**
   - Run regression tests again
   - Ensure performance is back within threshold
   - Update baseline if legitimate improvement

### Common Causes of Regressions

1. **N+1 Query Problems**
   - Missing eager loading
   - Inefficient relationship queries

2. **Cache Misses**
   - Cache not properly warmed
   - Cache invalidation issues

3. **Inefficient Algorithms**
   - Nested loops
   - Unnecessary iterations

4. **Memory Leaks**
   - Objects not properly released
   - Large arrays accumulating

5. **Database Issues**
   - Missing indexes
   - Slow queries
   - Lock contention

---

## Performance Monitoring Best Practices

### 1. Run Tests Regularly

- Run locally before committing
- Review CI results for every PR
- Monitor daily scheduled runs

### 2. Investigate All Regressions

- Don't ignore small regressions
- They can accumulate over time
- May indicate larger issues

### 3. Profile Before Optimizing

- Use profiling tools to identify bottlenecks
- Don't optimize based on assumptions
- Measure before and after changes

### 4. Document Performance Changes

- Note why baselines were updated
- Document optimization techniques used
- Share learnings with team

### 5. Set Realistic Thresholds

- Too strict: Many false positives
- Too loose: Miss real issues
- Adjust based on experience

---

## Integration with Development Workflow

### Pre-Commit

```bash
# Run performance tests before committing
./vendor/bin/phpunit --testsuite=Performance --filter=PerformanceRegressionTest
```

### Pre-Push

```bash
# Run full performance suite before pushing
./vendor/bin/phpunit --testsuite=Performance
```

### Code Review

- Review performance test results in PR
- Discuss any regressions with team
- Require fixes before merging

### Release Process

1. Run full performance test suite
2. Compare with previous release
3. Update baselines if appropriate
4. Document performance changes in release notes

---

## Continuous Improvement

### Tracking Performance Trends

Monitor performance over time:
- Weekly performance reports
- Monthly trend analysis
- Quarterly performance reviews

### Setting Performance Goals

- Establish performance targets
- Track progress towards goals
- Celebrate improvements

### Performance Culture

- Make performance a priority
- Share performance wins
- Learn from regressions
- Continuously optimize

---

## Advanced Usage

### Custom Metrics

Add custom performance metrics:

```php
public function test_custom_operation_no_regression(): void
{
    $baseline = 50.0; // ms
    $threshold = $baseline * 1.2; // 20% regression
    
    // Measure your operation
    $startTime = microtime(true);
    $this->customOperation();
    $endTime = microtime(true);
    $time = ($endTime - $startTime) * 1000;
    
    // Assert no regression
    $this->assertLessThan($threshold, $time);
}
```

### Conditional Thresholds

Different thresholds for different operations:

```php
private const THRESHOLDS = [
    'critical_operation' => 10.0, // 10% max regression
    'normal_operation' => 20.0,   // 20% max regression
    'low_priority' => 30.0,       // 30% max regression
];
```

### Performance Budgets

Set performance budgets for features:

```php
public function test_feature_performance_budget(): void
{
    $budget = 100; // ms
    
    $time = $this->measureFeaturePerformance();
    
    $this->assertLessThan(
        $budget,
        $time,
        "Feature exceeded performance budget"
    );
}
```

---

## Resources

### Documentation

- [Performance Testing Guide](./performance-testing.md)
- [Optimization Guide](../guides/performance-optimization.md)
- [Profiling Guide](../guides/profiling.md)

### Tools

- PHPUnit for test execution
- GitHub Actions for CI/CD
- Performance monitor script
- Profiling tools (Xdebug, Blackfire)

### External Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [PHP Performance Best Practices](https://www.php.net/manual/en/features.performance.php)

---

## Support

### Questions

- Check this documentation first
- Review test output and reports
- Ask in team discussions
- Consult with senior developers

### Reporting Issues

- Use GitHub issues for bugs
- Tag with `performance` label
- Include test output
- Provide steps to reproduce

---

**Document Version**: 1.0.0  
**Last Updated**: 2026-02-28  
**Status**: Active  
**Maintainer**: CanvaStack Team

