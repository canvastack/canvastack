<?php

namespace Canvastack\Canvastack\Console;

use Illuminate\Console\Command;

class CanvastackSnapshotValidateCommand extends Command
{
    protected $signature = 'canvastack:snapshot:validate {--path=} {--strict : Exit non-zero if any invalid file found}';

    protected $description = 'Validate datatable-inspector snapshot JSON files and their tolerance metadata.';

    public function handle(): int
    {
        $base = base_path();
        $defaultPath = $base.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'datatable-inspector';
        $path = $this->option('path') ?: $defaultPath;

        if (! is_dir($path)) {
            $this->error('Snapshot directory not found: '.$path);

            return self::FAILURE;
        }

        $files = glob($path.DIRECTORY_SEPARATOR.'*.json') ?: [];
        if (empty($files)) {
            $this->warn('No snapshot files found at: '.$path);

            return self::SUCCESS;
        }

        $invalid = 0;
        $total = 0;
        foreach ($files as $file) {
            $total++;
            $name = basename($file);
            $raw = file_get_contents($file);
            $json = json_decode((string) $raw, true);
            $errs = $this->validateSnapshotArray($json);
            if (! empty($errs)) {
                $invalid++;
                $this->line("[INVALID] {$name}");
                foreach ($errs as $e) {
                    $this->line("  - {$e}");
                }
            } else {
                $this->info("[OK] {$name}");
            }
        }

        $this->line("Validated {$total} file(s). Invalid: {$invalid}");
        if ($invalid > 0 && $this->option('strict')) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param  mixed  $snap
     * @return array<string>
     */
    private function validateSnapshotArray($snap): array
    {
        $errs = [];
        if (! is_array($snap)) {
            return ['File is not a JSON object'];
        }

        // table_name
        if (empty($snap['table_name']) || ! is_string($snap['table_name'])) {
            $errs[] = 'Missing or invalid table_name (string required)';
        }

        // columns.lists
        $lists = $snap['columns']['lists'] ?? null;
        if (! is_array($lists) || empty($lists)) {
            $errs[] = 'Missing or invalid columns.lists (non-empty array required)';
        }

        // columns.blacklist (optional but if present must be array)
        if (isset($snap['columns']['blacklist']) && ! is_array($snap['columns']['blacklist'])) {
            $errs[] = 'columns.blacklist must be an array when present';
        }

        // joins.foreign_keys (optional mapping table.col => table.col)
        if (isset($snap['joins']['foreign_keys'])) {
            $fks = $snap['joins']['foreign_keys'];
            if (! is_array($fks)) {
                $errs[] = 'joins.foreign_keys must be an object/map when present';
            } else {
                foreach ($fks as $left => $right) {
                    if (! is_string($left) || ! is_string($right) || strpos($left, '.') === false || strpos($right, '.') === false) {
                        $errs[] = 'joins.foreign_keys entries must be in the form "table.column":"table.column"';
                        break;
                    }
                }
            }
        }

        // tolerance (either root tolerance or diff.tolerance), must be numeric when present
        $tol = $snap['tolerance'] ?? ($snap['diff']['tolerance'] ?? null);
        if ($tol !== null && ! is_numeric($tol)) {
            $errs[] = 'tolerance must be numeric when present (either tolerance or diff.tolerance)';
        }

        // paging (optional minimal checks)
        if (isset($snap['paging']) && ! is_array($snap['paging'])) {
            $errs[] = 'paging must be an object when present';
        }

        return $errs;
    }
}
