<?php

namespace Canvastack\Canvastack\Console;

use Illuminate\Console\Command;

class CanvastackDbCheckCommand extends Command
{
    protected $signature = 'canvastack:db:check {--json : Output JSON only (no extra text)}';

    protected $description = 'Validate connectivity for required database connections and exit non-zero on failure.';

    public function handle(): int
    {
        $base = base_path();
        $script = $base.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'db-check.php';
        if (! is_file($script)) {
            $this->error('Script not found: '.$script);

            return self::FAILURE;
        }

        // Run the underlying script and proxy its exit code and output
        try {
            // Use PHP binary running current process
            $php = PHP_BINARY ?: 'php';
            $cmd = escapeshellarg($php).' '.escapeshellarg($script);
            $output = null;
            $retval = 0;
            exec($cmd.' 2>&1', $lines, $retval);
            $output = implode(PHP_EOL, $lines);

            // If --json, only print JSON (try to extract JSON body)
            if ($this->option('json')) {
                $json = $this->extractJson($output);
                if ($json !== null) {
                    $this->line($json);
                } else {
                    $this->warn('JSON output unavailable; raw output follows:');
                    $this->line($output);
                }
            } else {
                $this->line($output);
            }

            return $retval === 0 ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('[DBCheck] Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function extractJson(string $raw): ?string
    {
        // Attempt to locate JSON array/object in raw output
        $start = strpos($raw, '[');
        $endObj = strrpos($raw, '}');
        $endArr = strrpos($raw, ']');
        $end = max($endObj !== false ? $endObj : -1, $endArr !== false ? $endArr : -1);
        if ($start !== false && $end > $start) {
            $json = substr($raw, $start, $end - $start + 1);
            json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }

        return null;
    }
}
