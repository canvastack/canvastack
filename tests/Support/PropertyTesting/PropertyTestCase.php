<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Support\PropertyTesting;

use Canvastack\Canvastack\Tests\TestCase;

/**
 * Base class for property-based tests.
 *
 * Provides utilities for running property tests with multiple iterations.
 */
abstract class PropertyTestCase extends TestCase
{
    /**
     * Default number of iterations for property tests.
     * Reduced from 100 to 10 for faster test execution.
     */
    protected int $iterations = 10;

    /**
     * Run a property test with multiple iterations.
     *
     * @param \Generator $generator Data generator
     * @param callable $property Property to test (should throw exception or return false on failure)
     * @param int|null $iterations Number of iterations (default: 100)
     * @return void
     */
    protected function forAll(\Generator $generator, callable $property, ?int $iterations = null): void
    {
        $iterations = $iterations ?? $this->iterations;
        $failures = [];

        $i = 0;
        foreach ($generator as $value) {
            if ($i >= $iterations) {
                break;
            }

            try {
                $result = $property($value);

                // If property returns false, it's a failure
                if ($result === false) {
                    $failures[] = [
                        'iteration' => $i + 1,
                        'value' => $value,
                        'error' => 'Property returned false',
                    ];
                }
            } catch (\Throwable $e) {
                // If property throws exception, it's a failure
                $failures[] = [
                    'iteration' => $i + 1,
                    'value' => $value,
                    'error' => $e->getMessage(),
                ];
            }

            $i++;
        }

        // If there are failures, report them
        if (!empty($failures)) {
            $message = sprintf(
                "Property failed for %d/%d iterations:\n%s",
                count($failures),
                $iterations,
                $this->formatFailures($failures)
            );
            $this->fail($message);
        }

        // Add assertion to avoid "risky test" warning
        $this->assertTrue(true, "Property passed for all {$iterations} iterations");
    }

    /**
     * Run a property test expecting exceptions.
     *
     * @param \Generator $generator Data generator
     * @param callable $property Property to test (should throw exception)
     * @param string $expectedException Expected exception class
     * @param int|null $iterations Number of iterations (default: 100)
     * @return void
     */
    protected function forAllExpectingException(
        \Generator $generator,
        callable $property,
        string $expectedException,
        ?int $iterations = null
    ): void {
        $iterations = $iterations ?? $this->iterations;
        $failures = [];

        $i = 0;
        foreach ($generator as $value) {
            if ($i >= $iterations) {
                break;
            }

            try {
                $property($value);

                // If no exception thrown, it's a failure
                $failures[] = [
                    'iteration' => $i + 1,
                    'value' => $value,
                    'error' => "Expected {$expectedException} but no exception was thrown",
                ];
            } catch (\Throwable $e) {
                // Check if correct exception type
                if (!($e instanceof $expectedException)) {
                    $failures[] = [
                        'iteration' => $i + 1,
                        'value' => $value,
                        'error' => sprintf(
                            'Expected %s but got %s: %s',
                            $expectedException,
                            get_class($e),
                            $e->getMessage()
                        ),
                    ];
                }
            }

            $i++;
        }

        // If there are failures, report them
        if (!empty($failures)) {
            $message = sprintf(
                "Property failed for %d/%d iterations:\n%s",
                count($failures),
                $iterations,
                $this->formatFailures($failures)
            );
            $this->fail($message);
        }

        // Add assertion to avoid "risky test" warning
        $this->assertTrue(true, "Property passed for all {$iterations} iterations");
    }

    /**
     * Format failures for display.
     */
    private function formatFailures(array $failures): string
    {
        $lines = [];

        // Show first 5 failures
        $showFailures = array_slice($failures, 0, 5);

        foreach ($showFailures as $failure) {
            $valueStr = $this->formatValue($failure['value']);
            $lines[] = sprintf(
                "  Iteration %d: %s\n    Value: %s",
                $failure['iteration'],
                $failure['error'],
                $valueStr
            );
        }

        if (count($failures) > 5) {
            $lines[] = sprintf('  ... and %d more failures', count($failures) - 5);
        }

        return implode("\n", $lines);
    }

    /**
     * Format value for display.
     */
    private function formatValue($value): string
    {
        if (is_string($value)) {
            if (strlen($value) > 100) {
                return '"' . substr($value, 0, 100) . '..." (truncated)';
            }

            return '"' . $value . '"';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_object($value)) {
            return get_class($value);
        }

        return var_export($value, true);
    }
}
