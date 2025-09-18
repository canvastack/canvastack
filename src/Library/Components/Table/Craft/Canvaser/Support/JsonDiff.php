<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support;

use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

final class JsonDiff
{
    /**
     * Compare two DataTables-like payloads and return a shallow diff.
     * Always includes a concise summary so logs remain informative.
     */
    public static function compare($legacy, $pipeline): array
    {
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('JsonDiff: Starting payload comparison', [
                'has_legacy' => !empty($legacy),
                'has_pipeline' => !empty($pipeline),
                'pipeline_is_null' => $pipeline === null
            ]);
        }

        $diff = [];
        try {
            if ($pipeline === null) {
                return ['note' => 'pipeline_output_unavailable'];
            }

            $legacyArr = self::normalizePayload($legacy);
            $pipeArr = self::normalizePayload($pipeline);

            $keys = ['draw', 'recordsTotal', 'recordsFiltered', 'data'];
            foreach ($keys as $k) {
                $lv = $legacyArr[$k] ?? null;
                $pv = $pipeArr[$k] ?? null;

                if ($k === 'data') {
                    $lvLen = is_array($lv) ? count($lv) : null;
                    $pvLen = is_array($pv) ? count($pv) : null;
                    if ($lvLen !== $pvLen) {
                        $diff['data_length'] = ['legacy' => $lvLen, 'pipeline' => $pvLen];
                    }
                } elseif ($lv !== $pv) {
                    $diff[$k] = ['legacy' => $lv, 'pipeline' => $pv];
                }
            }

            // Always attach a small summary for visibility
            $summary = [
                'recordsTotal' => ['legacy' => $legacyArr['recordsTotal'] ?? null, 'pipeline' => $pipeArr['recordsTotal'] ?? null],
                'recordsFiltered' => ['legacy' => $legacyArr['recordsFiltered'] ?? null, 'pipeline' => $pipeArr['recordsFiltered'] ?? null],
                'data_length' => [
                    'legacy' => isset($legacyArr['data']) && is_array($legacyArr['data']) ? count($legacyArr['data']) : null,
                    'pipeline' => isset($pipeArr['data']) && is_array($pipeArr['data']) ? count($pipeArr['data']) : null,
                ],
            ];
            if (empty($diff)) {
                $diff = ['note' => 'no_diff', 'summary' => $summary];
            } else {
                $diff['summary'] = $summary;
            }
        } catch (\Throwable $e) {
            $diff['error'] = $e->getMessage();
        }

        return $diff;
    }

    private static function normalizePayload($payload): array
    {
        // If Response or JsonResponse, attempt to extract content
        if (is_object($payload)) {
            // Laravel responses implement getContent()
            if (method_exists($payload, 'getContent')) {
                $content = $payload->getContent();
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (is_array($payload)) {
            return $payload;
        }

        // Fallback: try array cast via json re-encode
        return json_decode(json_encode($payload), true) ?: [];
    }
}
