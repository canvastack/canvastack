<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Support\PropertyTesting;

/**
 * Property-Based Testing Generator.
 *
 * Provides generators for property-based testing with PHPUnit.
 * Inspired by QuickCheck and Hypothesis.
 */
class Generator
{
    /**
     * Generate random strings.
     */
    public static function string(int $minLength = 0, int $maxLength = 100): \Generator
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 ';

        for ($i = 0; $i < 100; $i++) {
            $length = rand($minLength, $maxLength);
            $result = '';
            for ($j = 0; $j < $length; $j++) {
                $result .= $chars[rand(0, strlen($chars) - 1)];
            }
            yield $result;
        }
    }

    /**
     * Generate strings containing specific substrings.
     */
    public static function stringContaining(array $substrings, int $minLength = 10, int $maxLength = 100): \Generator
    {
        for ($i = 0; $i < 100; $i++) {
            $substring = $substrings[array_rand($substrings)];
            $maxPrefixLength = max(0, (int) (($maxLength - strlen($substring)) / 2));
            $prefix = self::randomString(rand(0, $maxPrefixLength));
            $maxSuffixLength = max(0, $maxLength - strlen($substring) - strlen($prefix));
            $suffix = self::randomString(rand(0, $maxSuffixLength));
            yield $prefix . $substring . $suffix;
        }
    }

    /**
     * Generate dangerous SQL statements.
     */
    public static function dangerousSQL(): \Generator
    {
        $keywords = ['DROP', 'TRUNCATE', 'DELETE', 'UPDATE', 'INSERT', 'ALTER'];
        $templates = [
            'SELECT * FROM users; {keyword} TABLE users',
            '{keyword} TABLE users WHERE 1=1',
            'SELECT * FROM ({keyword} TABLE users) AS t',
            'SELECT * FROM users WHERE id = 1; {keyword} TABLE posts',
            '{keyword} DATABASE test',
            'SELECT * FROM users UNION {keyword} TABLE users',
        ];

        for ($i = 0; $i < 100; $i++) {
            $keyword = $keywords[array_rand($keywords)];
            $template = $templates[array_rand($templates)];
            yield str_replace('{keyword}', $keyword, $template);
        }
    }

    /**
     * Generate malicious HTML/XSS payloads.
     */
    public static function maliciousHTML(): \Generator
    {
        $patterns = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror=alert("XSS")>',
            'javascript:alert("XSS")',
            '<iframe src="javascript:alert(\'XSS\')">',
            '<svg onload=alert("XSS")>',
            '<body onload=alert("XSS")>',
            '<input onfocus=alert("XSS") autofocus>',
            '<select onfocus=alert("XSS") autofocus>',
            '<textarea onfocus=alert("XSS") autofocus>',
            '<marquee onstart=alert("XSS")>',
            '<div style="background:url(javascript:alert(\'XSS\'))">',
            '<link rel="stylesheet" href="javascript:alert(\'XSS\')">',
        ];

        for ($i = 0; $i < 100; $i++) {
            $pattern = $patterns[array_rand($patterns)];
            $prefix = self::randomString(rand(0, 10));
            $suffix = self::randomString(rand(0, 10));
            yield $prefix . $pattern . $suffix;
        }
    }

    /**
     * Generate malicious HTML attributes.
     */
    public static function maliciousAttributes(): \Generator
    {
        $eventHandlers = [
            'onclick', 'onload', 'onerror', 'onmouseover', 'onfocus',
            'onblur', 'onchange', 'onsubmit', 'onkeypress', 'onkeydown',
        ];

        $maliciousValues = [
            'javascript:alert("XSS")',
            'data:text/html,<script>alert("XSS")</script>',
            'vbscript:msgbox("XSS")',
            'file:///etc/passwd',
        ];

        for ($i = 0; $i < 100; $i++) {
            if (rand(0, 1)) {
                // Event handler
                $key = $eventHandlers[array_rand($eventHandlers)];
                $value = 'alert("XSS")';
            } else {
                // Malicious URL
                $key = 'href';
                $value = $maliciousValues[array_rand($maliciousValues)];
            }
            yield [$key => $value];
        }
    }

    /**
     * Generate invalid column names.
     */
    public static function invalidColumns(array $validColumns): \Generator
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz_0123456789';

        for ($i = 0; $i < 100; $i++) {
            do {
                $column = '';
                $length = rand(1, 20);
                for ($j = 0; $j < $length; $j++) {
                    $column .= $chars[rand(0, strlen($chars) - 1)];
                }
            } while (in_array($column, $validColumns));

            yield $column;
        }
    }

    /**
     * Generate invalid table names.
     */
    public static function invalidTables(array $validTables): \Generator
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz_0123456789';

        for ($i = 0; $i < 100; $i++) {
            do {
                $table = '';
                $length = rand(1, 20);
                for ($j = 0; $j < $length; $j++) {
                    $table .= $chars[rand(0, strlen($chars) - 1)];
                }
            } while (in_array($table, $validTables));

            yield $table;
        }
    }

    /**
     * Generate invalid hex colors.
     */
    public static function invalidHexColors(): \Generator
    {
        $invalid = [
            '#12345',      // Too short
            '#1234567',    // Too long
            '#GGGGGG',     // Invalid chars
            '123456',      // Missing #
            '#12-34-56',   // Invalid format
            'rgb(0,0,0)',  // Wrong format
            'red',         // Named color
            '#12 34 56',   // Spaces
        ];

        for ($i = 0; $i < 100; $i++) {
            yield $invalid[array_rand($invalid)];
        }
    }

    /**
     * Generate integers.
     */
    public static function integer(int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): \Generator
    {
        for ($i = 0; $i < 100; $i++) {
            yield rand($min, $max);
        }
    }

    /**
     * Generate positive integers.
     */
    public static function positiveInteger(int $max = 10000): \Generator
    {
        for ($i = 0; $i < 100; $i++) {
            yield rand(1, $max);
        }
    }

    /**
     * Generate negative integers.
     */
    public static function negativeInteger(int $min = -10000): \Generator
    {
        for ($i = 0; $i < 100; $i++) {
            yield rand($min, -1);
        }
    }

    /**
     * Generate arrays of specific type.
     */
    public static function arrayOf(\Generator $generator, int $minSize = 0, int $maxSize = 10): \Generator
    {
        for ($i = 0; $i < 100; $i++) {
            $size = rand($minSize, $maxSize);
            $array = [];
            $gen = $generator;
            for ($j = 0; $j < $size; $j++) {
                $gen->next();
                $array[] = $gen->current();
            }
            yield $array;
        }
    }

    /**
     * Generate elements from array.
     */
    public static function elements(array $elements): \Generator
    {
        for ($i = 0; $i < 100; $i++) {
            yield $elements[array_rand($elements)];
        }
    }

    /**
     * Generate booleans.
     */
    public static function boolean(): \Generator
    {
        for ($i = 0; $i < 100; $i++) {
            yield (bool) rand(0, 1);
        }
    }

    /**
     * Helper: Generate random string.
     */
    private static function randomString(int $length): string
    {
        if ($length <= 0) {
            return '';
        }

        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789 ';
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[rand(0, strlen($chars) - 1)];
        }

        return $result;
    }

    /**
     * Generate row counts for N+1 testing.
     *
     * Returns different row counts to test that query count doesn't scale with data size.
     */
    public static function rowCounts(): \Generator
    {
        $counts = [10, 50, 100];

        for ($i = 0; $i < 100; $i++) {
            yield $counts[array_rand($counts)];
        }
    }

    /**
     * Generate relationship counts for N+1 testing.
     *
     * Returns number of unique relationship types to test.
     */
    public static function relationshipCounts(): \Generator
    {
        for ($i = 0; $i < 100; $i++) {
            yield rand(1, 3);
        }
    }

    /**
     * Generate valid user field names.
     *
     * Returns field names that exist in the users table schema.
     */
    public static function userFields(): \Generator
    {
        $fields = ['id', 'name', 'email', 'password', 'is_super_admin', 'active', 'created_at', 'updated_at'];

        for ($i = 0; $i < 100; $i++) {
            yield $fields[array_rand($fields)];
        }
    }

    /**
     * Generate valid post field names.
     *
     * Returns field names that exist in the posts table schema.
     */
    public static function postFields(): \Generator
    {
        $fields = ['id', 'title', 'content', 'user_id', 'status', 'featured', 'excerpt', 'metadata', 'created_at', 'updated_at'];

        for ($i = 0; $i < 100; $i++) {
            yield $fields[array_rand($fields)];
        }
    }

    /**
     * Generate valid post status values.
     *
     * Returns valid status values for posts table.
     */
    public static function postStatus(): \Generator
    {
        $statuses = ['draft', 'published', 'archived'];

        for ($i = 0; $i < 100; $i++) {
            yield $statuses[array_rand($statuses)];
        }
    }

    /**
     * Generate valid province field names.
     *
     * Returns field names that exist in the test_provinces table schema.
     */
    public static function provinceFields(): \Generator
    {
        $fields = ['id', 'name', 'code', 'created_at', 'updated_at'];

        for ($i = 0; $i < 100; $i++) {
            yield $fields[array_rand($fields)];
        }
    }

    /**
     * Generate valid city field names.
     *
     * Returns field names that exist in the test_cities table schema.
     */
    public static function cityFields(): \Generator
    {
        $fields = ['id', 'province_id', 'name', 'code', 'created_at', 'updated_at'];

        for ($i = 0; $i < 100; $i++) {
            yield $fields[array_rand($fields)];
        }
    }

    /**
     * Generate valid table field configurations.
     *
     * Returns arrays of field names suitable for TableBuilder testing.
     */
    public static function tableFields(): \Generator
    {
        $fieldOptions = [
            ['id'],
            ['name'],
            ['email'],
            ['id', 'name'],
            ['id', 'email'],
            ['name', 'email'],
            ['id', 'name', 'email'],
            ['name', 'email', 'active'],
            ['id', 'name', 'email', 'active'],
            ['id', 'name', 'email', 'created_at'],
        ];

        for ($i = 0; $i < 100; $i++) {
            yield $fieldOptions[array_rand($fieldOptions)];
        }
    }

    /**
     * Generate valid sortable column names.
     *
     * Returns column names that can be used for sorting in tests.
     */
    public static function sortableColumns(): \Generator
    {
        $columns = ['id', 'name', 'email', 'created_at', 'updated_at'];

        for ($i = 0; $i < 100; $i++) {
            yield $columns[array_rand($columns)];
        }
    }

    /**
     * Generate valid searchable column names.
     *
     * Returns column names that can be used for searching in tests.
     */
    public static function searchableColumns(): \Generator
    {
        $columns = ['name', 'email', 'title', 'content'];

        for ($i = 0; $i < 100; $i++) {
            yield $columns[array_rand($columns)];
        }
    }

    /**
     * Generate valid JSON paths for metadata column.
     *
     * Returns JSON paths that exist in the posts.metadata column.
     */
    public static function jsonPaths(): \Generator
    {
        $paths = [
            'seo.title',
            'seo.description',
            'seo.keywords',
            'social.image',
            'social.title',
            'layout.sidebar',
            'layout.header',
        ];

        for ($i = 0; $i < 100; $i++) {
            yield $paths[array_rand($paths)];
        }
    }
}
