<?php

namespace Canvastack\Canvastack\Console;

use Canvastack\Canvastack\Models\Admin\System\Modules;
use Canvastack\Canvastack\Models\Admin\System\User;
use Illuminate\Console\Command;

class CanvastackRelationBenchCommand extends Command
{
    protected $signature = 'canvastack:relation:bench
                            {target : modules_privileges|users_groups}
                            {--group= : Group id for privileges()}
                            {--page-type= : Page type for privileges() (frontend|admin)}
                            {--mode=join : users_groups mode: join|eager|lazy}
                            {--limit=50 : Row limit for users_groups}
                            {--repeat=5 : Number of runs}
                            {--warmup=1 : Warm-up runs to ignore}
                            {--json : Output JSON}';

    protected $description = 'Benchmark relation-heavy operations: Modules::privileges() and Users group joins.';

    public function handle(): int
    {
        $target = (string) $this->argument('target');
        $repeat = max(1, (int) $this->option('repeat'));
        $warmup = max(0, (int) $this->option('warmup'));

        $runs = [];
        $extra = null;

        for ($i = 0; $i < $repeat; $i++) {
            $start = microtime(true);
            try {
                switch ($target) {
                    case 'modules_privileges':
                        $group = $this->option('group');
                        if ($group === null) {
                            $group = 1;
                        }
                        $pageType = $this->option('page-type') ?: 'admin';
                        $mod = new Modules();
                        // Simulate minimal session/env needed
                        $menu = $mod->privileges((int) $group, $pageType, false);
                        $count = is_iterable($menu) ? (is_countable($menu) ? count($menu) : iterator_count((function () use ($menu) {
                            foreach ($menu as $_) {
                                yield 1;
                            }
                        })())) : 0;
                        $extra = [
                            'routes' => $mod->route_path ?? [],
                            'roles_count' => isset($mod->roles) && is_array($mod->roles) ? count($mod->roles) : 0,
                            'privileges_count' => isset($mod->privileges) && is_array($mod->privileges) ? count($mod->privileges) : 0,
                            'menu_count' => $count,
                        ];
                        break;

                    case 'users_groups':
                        $mode = strtolower((string) ($this->option('mode') ?? 'join'));
                        $limit = max(1, (int) $this->option('limit'));
                        $rowsCount = 0;
                        if ($mode === 'join') {
                            // Single query join (baseline)
                            $q = (new User())->getUserInfo(false, false);
                            $rows = $q->limit($limit)->get();
                            $rowsCount = count($rows);
                        } elseif ($mode === 'eager') {
                            // Eager load relation (avoid N+1)
                            $rows = User::query()->with('group')->limit($limit)->get();
                            // Touch relation collection
                            foreach ($rows as $u) {
                                $rowsCount += ($u->group ? $u->group->count() : 0);
                            }
                        } elseif ($mode === 'lazy') {
                            // Intentional N+1 by accessing relation lazily
                            $rows = User::query()->limit($limit)->get();
                            foreach ($rows as $u) {
                                $rowsCount += $u->group()->count();
                            }
                        } else {
                            throw new \InvalidArgumentException('Invalid --mode. Use join|eager|lazy');
                        }
                        $extra = [
                            'mode' => $mode,
                            'limit' => $limit,
                            'rows_processed' => $rowsCount,
                        ];
                        break;

                    default:
                        $this->error('Unknown target. Use: modules_privileges|users_groups');

                        return self::FAILURE;
                }
                $runs[] = ['ms' => (microtime(true) - $start) * 1000.0];
            } catch (\Throwable $e) {
                $runs[] = ['ms' => (microtime(true) - $start) * 1000.0, 'error' => $e->getMessage()];
            }
        }

        $valid = array_slice($runs, max(0, min($warmup, count($runs))));
        $times = array_map(fn ($r) => (float) ($r['ms'] ?? 0), $valid);
        sort($times);
        $avg = count($times) ? array_sum($times) / count($times) : 0.0;
        $min = count($times) ? min($times) : 0.0;
        $max = count($times) ? max($times) : 0.0;
        $median = count($times) ? ($times[(int) floor((count($times) - 1) / 2)] + $times[(int) ceil((count($times) - 1) / 2)]) / 2 : 0.0;
        $p95 = 0.0;
        if (count($times)) {
            $idx = (int) floor(0.95 * (count($times) - 1));
            $p95 = $times[$idx];
        }

        $out = [
            'target' => $target,
            'repeat' => $repeat,
            'warmup_ignored' => $warmup,
            'stats' => [
                'avg_ms' => round($avg, 2),
                'min_ms' => round($min, 2),
                'max_ms' => round($max, 2),
                'median_ms' => round($median, 2),
                'p95_ms' => round($p95, 2),
            ],
            'runs' => $runs,
            'extra' => $extra,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($out, JSON_PRETTY_PRINT));
        } else {
            $this->info('Relation Bench Summary');
            $this->line('Target: '.$target);
            $this->line('Avg: '.round($avg, 2).' ms | P95: '.round($p95, 2).' ms');
        }

        return self::SUCCESS;
    }
}
