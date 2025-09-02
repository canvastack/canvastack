<?php

namespace Tests\Feature;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support\HybridCompare;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

require_once __DIR__.'/../Support/LegacyHelperStubs.php';

final class DatatablesInspectorReplayTest extends TestCase
{
    public static function scenarioProvider(): array
    {
        $dir = 'd:\\worksites\\mantra.smartfren.dev\\storage\\app\\datatable-inspector';
        $files = glob($dir.DIRECTORY_SEPARATOR.'*.json');
        $cases = [];
        foreach ($files as $file) {
            $json = json_decode((string) file_get_contents($file), true);
            if (! is_array($json)) {
                continue;
            }
            // Require minimally: table_name and columns.lists
            if (empty($json['table_name']) || empty($json['columns']['lists'])) {
                continue;
            }
            $cases[basename($file)] = [$json];
        }
        // Fallback single case if empty
        if (empty($cases)) {
            $cases['fallback_users.json'] = [[
                'table_name' => 'users',
                'columns' => ['lists' => ['id', 'name', 'email'], 'blacklist' => ['password', 'action', 'no']],
                'joins' => ['foreign_keys' => [], 'selected' => ['users.*']],
                'filters' => ['where' => [], 'applied' => [], 'raw_params' => []],
                'paging' => ['start' => 0, 'length' => 10, 'total' => 3],
            ]];
        }

        return $cases;
    }

    /**
     * @dataProvider scenarioProvider
     */
    public function test_replay_snapshot_runs_legacy_and_pipeline(array $snap): void
    {
        $table = $snap['table_name'];
        $lists = $snap['columns']['lists'] ?? [];
        $blacklist = $snap['columns']['blacklist'] ?? ['action', 'no'];
        $orderby = $snap['columns']['orderby'] ?? ['column' => $lists[0] ?? 'id', 'order' => 'desc'];
        $foreignKeys = $snap['joins']['foreign_keys'] ?? [];

        // Normalize FK mappings to determine join tables and required columns
        $joinTables = [];
        $baseFkCols = [];
        $joinColsSpec = []; // table => [cols]
        foreach ((array) $foreignKeys as $left => $right) {
            // leftJoin(joinTable, left, '=', right) -- per legacy Datatables
            // Example snapshot: {"users.id":"posts.user_id"} when base table is "posts"
            [$leftTable, $leftCol] = explode('.', $left);
            [$rightTable, $rightCol] = explode('.', $right);

            // Always ensure tables exist for both sides
            $joinTables[$leftTable] = true;
            $joinTables[$rightTable] = true;
            // Collect column specs for both sides
            $joinColsSpec[$leftTable][$leftCol] = true;
            $joinColsSpec[$rightTable][$rightCol] = true;

            // Identify which side is base table to know base FK columns
            if ($rightTable === $table) {
                $baseFkCols[$rightCol] = true;
            } elseif ($leftTable === $table) {
                $baseFkCols[$leftCol] = true;
            }
        }

        // 1) Build minimal schema for base table
        Schema::dropIfExists($table);
        Schema::create($table, function (Blueprint $t) use ($lists, $baseFkCols) {
            // ensure 'id' exists
            $t->increments('id');
            $added = ['id' => true];
            // columns from lists
            foreach ($lists as $col) {
                if (isset($added[$col])) {
                    continue;
                }
                $type = $this->columnTypeGuess($col);
                $this->applyColumn($t, $type, $col);
                $added[$col] = true;
            }
            // ensure base table FK columns exist
            foreach (array_keys($baseFkCols) as $fkCol) {
                if (! isset($added[$fkCol])) {
                    $this->applyColumn($t, 'integer', $fkCol);
                    $added[$fkCol] = true;
                }
            }
        });

        // 2) For foreign keys, create joined tables and add columns
        foreach (array_keys($joinTables) as $jt) {
            if ($jt === $table) {
                continue;
            }
            if (! Schema::hasTable($jt)) {
                Schema::create($jt, function (Blueprint $t) use ($joinColsSpec, $jt) {
                    $t->increments('id');
                    $added = ['id' => true];
                    foreach (array_keys($joinColsSpec[$jt] ?? []) as $col) {
                        if (isset($added[$col])) {
                            continue;
                        }
                        if ($col === 'id') {
                            continue;
                        }
                        $type = $this->columnTypeGuess($col);
                        $this->applyColumn($t, $type, $col);
                        $added[$col] = true;
                    }
                    // also add common display fields to help select()
                    if (! isset($added['name'])) {
                        $t->string('name')->nullable();
                    }
                    if (! isset($added['email'])) {
                        $t->string('email')->nullable();
                    }
                });
            }
        }

        // 3) Seed a few rows for base and join tables
        DB::table($table)->delete();
        DB::table($table)->insert([
            ['id' => 1] + $this->fakeRow($lists),
            ['id' => 2] + $this->fakeRow($lists),
            ['id' => 3] + $this->fakeRow($lists),
        ]);
        foreach (array_keys($joinTables) as $jt) {
            if ($jt === $table) {
                continue;
            }
            if (DB::table($jt)->count() === 0) {
                DB::table($jt)->insert([
                    ['id' => 1, 'name' => $jt.'-1', 'email' => $jt.'1@example.test'],
                    ['id' => 2, 'name' => $jt.'-2', 'email' => $jt.'2@example.test'],
                ]);
            }
        }
        // Set FK values on base rows
        foreach (array_keys($baseFkCols) as $fkCol) {
            if (Schema::hasColumn($table, $fkCol)) {
                DB::table($table)->where('id', 1)->update([$fkCol => 1]);
                DB::table($table)->where('id', 2)->update([$fkCol => 2]);
            }
        }

        // 4) Build Eloquent model for base table
        $model = new class extends Model
        {
            public $timestamps = false;

            protected $guarded = [];
        };
        // set table name after instantiation to be compatible with Eloquent events
        $model->setTable($table);

        // 5) Build datatables input structure compatible with legacy Datatables
        $method = ['difta' => ['name' => $table]];
        $data = (object) [
            'datatables' => (object) [
                'model' => [
                    $table => [
                        'type' => 'model',
                        'source' => $model,
                    ],
                ],
                'columns' => [
                    $table => array_filter([
                        'lists' => $lists,
                        'blacklist' => $blacklist,
                        'foreign_keys' => $foreignKeys,
                        'orderby' => $orderby,
                    ]),
                ],
                'records' => ['index_lists' => false],
                'button_removed' => [],
                'useFieldTargetURL' => 'id',
            ],
        ];

        // 6) Execute HybridCompare: legacy + pipeline preflight
        $result = HybridCompare::run($method, $data, [], []);

        // 7) Assert shape
        $this->assertIsArray($result);
        $this->assertArrayHasKey('legacy_result', $result);
        $this->assertArrayHasKey('diff', $result);

        // 8) Evaluate diff severity per snapshot and fail if above thresholds
        $severity = $this->computeSeverityScore($result['diff']);
        // threshold can be provided by snapshot (snap[threshold] or snap[diff][tolerance]) or env
        $envTolRaw = getenv('CANVASTACK_DT_DIFF_TOLERANCE');
        $envTol = is_numeric($envTolRaw) ? (int) $envTolRaw : null;
        $tolerance = $snap['tolerance']
            ?? ($snap['diff']['tolerance'] ?? null)
            ?? ($envTol !== null ? $envTol : 100);
        $this->assertLessThanOrEqual(
            $tolerance,
            $severity,
            "Datatables diff severity {$severity} exceeds tolerance {$tolerance} for table {$table}"
        );
    }

    private function fakeRow(array $cols): array
    {
        $row = [];
        foreach ($cols as $c) {
            if ($c === 'id') {
                continue;
            }
            $n = strtolower($c);
            $type = $this->columnTypeGuess($c);
            switch ($type) {
                case 'integer': $row[$c] = 1;
                break;
                case 'boolean': $row[$c] = true;
                break;
                case 'datetime': $row[$c] = now()->format('Y-m-d H:i:s');
                break;
                case 'timestamp': $row[$c] = now()->format('Y-m-d H:i:s');
                break;
                case 'date': $row[$c] = now()->format('Y-m-d');
                break;
                case 'time': $row[$c] = now()->format('H:i:s');
                break;
                case 'decimal': $row[$c] = 1234.560000;
                break;
                case 'decimal_latlng': $row[$c] = (str_contains($n, 'lat') ? -6.20000000 : 106.81666600);
                break;
                case 'text': $row[$c] = str_repeat(substr($c.'-lorem ', 0, 20), 5);
                break;
                case 'json': $row[$c] = json_encode(['k' => $c, 'v' => 'val']);
                break;
                case 'uuid': $row[$c] = '00000000-0000-4000-8000-000000000000';
                break;
                case 'ip': $row[$c] = '127.0.0.1';
                break;
                case 'url': $row[$c] = 'https://example.test/'.$c;
                break;
                default:
                    if ($n === 'email') {
                        $row[$c] = $c.'@example.test';
                    } elseif (str_contains($n, 'phone') || str_contains($n, 'msisdn') || str_contains($n, 'mobile')) {
                        $row[$c] = '08123456789';
                    } elseif (str_contains($n, 'slug')) {
                        $row[$c] = 'sample-'.$c;
                    } elseif (str_contains($n, 'timezone') || $n === 'tz') {
                        $row[$c] = 'UTC';
                    } else {
                        $row[$c] = substr($c.'-val', 0, 50);
                    }
                    break;
            }
        }

        return $row;
    }

    private function columnTypeGuess(string $name): string
    {
        $n = strtolower($name);
        // IDs & UUID
        if ($n === 'id' || str_ends_with($n, '_id')) {
            return 'integer';
        }
        if ($n === 'uuid' || str_contains($n, 'uuid') || str_contains($n, 'guid')) {
            return 'uuid';
        }
        // Booleans
        if ($n === 'active' || $n === 'enabled' || $n === 'disabled' || str_starts_with($n, 'is_') || str_starts_with($n, 'has_') || str_ends_with($n, '_flag')) {
            return 'boolean';
        }
        // Date/Time
        if (str_ends_with($n, '_at')) {
            return 'timestamp';
        }
        if ($n === 'created_at' || $n === 'updated_at' || $n === 'deleted_at') {
            return 'timestamp';
        }
        if (preg_match('/(^|_)date($|_)/', $n)) {
            return 'date';
        }
        if (preg_match('/(^|_)time($|_)/', $n)) {
            return 'time';
        }
        if (str_contains($n, 'datetime')) {
            return 'datetime';
        }
        // Numbers
        if (str_contains($n, 'qty') || str_contains($n, 'count') || str_contains($n, 'number') || $n === 'year' || str_ends_with($n, '_year') || str_contains($n, 'age')) {
            return 'integer';
        }
        // Currency/Decimal
        if (str_contains($n, 'amount') || str_contains($n, 'price') || str_contains($n, 'cost') || str_contains($n, 'balance') || str_contains($n, 'total') || str_contains($n, 'rate') || str_contains($n, 'ratio') || str_contains($n, 'percent') || str_contains($n, 'percentage')) {
            return 'decimal';
        }
        // Geo
        if ($n === 'lat' || $n === 'latitude' || $n === 'lng' || $n === 'long' || $n === 'longitude' || str_contains($n, 'latitude') || str_contains($n, 'longitude')) {
            return 'decimal_latlng';
        }
        // JSON-like
        if (str_contains($n, 'json') || str_contains($n, 'meta') || str_contains($n, 'payload') || str_contains($n, 'attrs') || str_contains($n, 'attributes')) {
            return 'json';
        }
        // IP/URL/Email/Phone
        if (str_contains($n, 'ip')) {
            return 'ip';
        }
        if (str_contains($n, 'url') || str_contains($n, 'uri') || str_contains($n, 'link')) {
            return 'url';
        }
        if ($n === 'email') {
            return 'string';
        }
        if (str_contains($n, 'phone') || str_contains($n, 'msisdn') || str_contains($n, 'mobile')) {
            return 'string';
        }
        // Long text
        if (str_contains($n, 'desc') || str_contains($n, 'description') || str_contains($n, 'notes') || str_contains($n, 'remark') || str_contains($n, 'comment') || str_contains($n, 'message')) {
            return 'text';
        }
        // Fallback
        return 'string';
    }

    private function applyColumn(Blueprint $t, string $type, string $name): void
    {
        switch ($type) {
            case 'integer': $t->integer($name)->nullable();
            break;
            case 'boolean': $t->boolean($name)->nullable();
            break;
            case 'datetime': $t->dateTime($name)->nullable();
            break;
            case 'timestamp': $t->timestamp($name)->nullable();
            break;
            case 'date': $t->date($name)->nullable();
            break;
            case 'time': $t->time($name)->nullable();
            break;
            case 'decimal': $t->decimal($name, 18, 6)->nullable();
            break;
            case 'decimal_latlng': $t->decimal($name, 12, 8)->nullable();
            break;
            case 'text': $t->text($name)->nullable();
            break;
            case 'json': if (method_exists($t, 'json')) {
                $t->json($name)->nullable();
            } else {
                $t->text($name)->nullable();
            } break;
            case 'uuid': if (method_exists($t, 'uuid')) {
                $t->uuid($name)->nullable();
            } else {
                $t->string($name, 36)->nullable();
            } break;
            case 'ip': $t->string($name, 45)->nullable();
            break;
            case 'url': $t->string($name, 2048)->nullable();
            break;
            default: $t->string($name)->nullable();
            break;
        }
    }

    private function computeSeverityScore(array $diff): int
    {
        // If pipeline unavailable, set severity high (force attention)
        if (($diff['note'] ?? null) === 'pipeline_output_unavailable') {
            return 1000;
        }
        $score = 0;
        // data length mismatch weighs more
        if (isset($diff['data_length'])) {
            $score += 50;
        }
        if (isset($diff['recordsTotal'])) {
            $score += 30;
        }
        if (isset($diff['recordsFiltered'])) {
            $score += 30;
        }
        if (isset($diff['draw'])) {
            $score += 5;
        }
        // penalize presence of error
        if (isset($diff['error'])) {
            $score += 100;
        }
        // additional unknown keys add small penalties
        foreach ($diff as $k => $v) {
            if (in_array($k, ['note', 'summary', 'data_length', 'recordsTotal', 'recordsFiltered', 'draw', 'error'], true)) {
                continue;
            }
            $score += 1;
        }

        return $score;
    }
}
