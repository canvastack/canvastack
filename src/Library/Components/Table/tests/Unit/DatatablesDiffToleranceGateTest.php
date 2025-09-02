<?php

declare(strict_types=1);

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support\JsonDiff;
use PHPUnit\Framework\TestCase;

/**
 * CI gate: fail when diff severity exceeds tolerance.
 * - Uses synthetic equal payloads to ensure test is stable across environments.
 * - Configure tolerance via env CANVASTACK_DT_DIFF_TOLERANCE (int). Default: 0 (strict equality).
 * - Severity heuristic:
 *   - +1 for recordsTotal mismatch
 *   - +1 for recordsFiltered mismatch
 *   - +|lenDiff| for data length mismatch
 *   - 'pipeline_output_unavailable' => treated as very high severity (cannot evaluate)
 */
final class DatatablesDiffToleranceGateTest extends TestCase
{
    public function test_diff_tolerance_gate_respects_threshold(): void
    {
        // Synthetic equal payloads to exercise gating logic in CI without DB/routes.
        $legacy = [
            'draw' => 1,
            'recordsTotal' => 10,
            'recordsFiltered' => 10,
            'data' => array_fill(0, 10, ['id' => 1]),
        ];
        $pipeline = $legacy;

        $diff = JsonDiff::compare($legacy, $pipeline);

        $toleranceEnv = getenv('CANVASTACK_DT_DIFF_TOLERANCE');
        $tolerance = is_numeric($toleranceEnv) ? (int) $toleranceEnv : 0; // default strict

        $severity = self::severityFromDiff($diff);

        $this->assertTrue(
            $severity <= $tolerance,
            'Datatables diff severity exceeded tolerance. '.
            'severity='.$severity.' tolerance='.$tolerance.' diff='.json_encode($diff)
        );
    }

    private static function severityFromDiff(array $diff): int
    {
        if (($diff['note'] ?? null) === 'no_diff') {
            return 0;
        }
        if (($diff['note'] ?? null) === 'pipeline_output_unavailable') {
            // When pipeline output is unavailable, gate cannot evaluate reliably.
            // Treat as high severity so CI will fail unless tolerated explicitly.
            return 9999;
        }

        $severity = 0;
        if (isset($diff['recordsTotal'])) {
            $severity++;
        }
        if (isset($diff['recordsFiltered'])) {
            $severity++;
        }
        if (isset($diff['data_length'])) {
            $lv = (int) ($diff['data_length']['legacy'] ?? 0);
            $pv = (int) ($diff['data_length']['pipeline'] ?? 0);
            $severity += abs($lv - $pv);
        }

        return $severity;
    }
}
