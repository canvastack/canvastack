<?php

namespace Canvastack\Canvastack\Library\Components\Utility\Table;

final class FormulaColumns
{
    /**
     * Insert formula columns into existing columns according to legacy rules.
     * $columns: array of column keys
     * $data: array of formula definitions (name,label,field_lists,node_location,node_after)
     */
    public static function set(array $columns, array $data): array
    {
        // Validation: skip entries missing required keys
        $normalized = [];
        foreach ($data as $formula) {
            if (!is_array($formula)) continue;
            if (!isset($formula['name'], $formula['field_lists'])) continue;
            $normalized[] = $formula;
        }

        // Legacy expects reverse priority using arsort on raw $data (keep as-is where possible)
        // But since we normalized, we can keep given order; callers may pre-sort.
        $keyColumns = array_flip($columns);
        $hasAction = array_key_exists('action', $keyColumns);
        $hasNumberLists = array_key_exists('number_lists', $keyColumns);

        $fNode = [];
        foreach ($normalized as $f) {
            $node = $f['node_location'] ?? null;
            $name = $f['name'];
            $fNode[$name]['field_label'] = $f['label'] ?? $name;

            if (empty($node)) {
                $fNode[$name]['field_name'] = end($f['field_lists']);
            } else {
                if ($node === 'first') {
                    $fNode[$name]['field_name'] = $columns[0] ?? (end($columns) ?: $name);
                } elseif ($node === 'last') {
                    $fNode[$name]['field_name'] = $columns[array_key_last($columns)] ?? (end($columns) ?: $name);
                } else {
                    // custom target field name
                    $fNode[$name]['field_name'] = $node;
                }
            }

            if (!isset($keyColumns[$fNode[$name]['field_name']])) {
                // If custom target not found, try to interpret node_location as an index
                if (is_numeric($node)) {
                    $idx = (int) $node;
                    if ($idx >= 0 && $idx < count($columns)) {
                        $fNode[$name]['field_key'] = $idx;
                        $fNode[$name]['node_after'] = (bool)($f['node_after'] ?? false);
                        $fNode[$name]['node_location'] = $node;
                        continue;
                    }
                }
                // Target field not found → skip this formula insertion
                unset($fNode[$name]);
                continue;
            }

            $fNode[$name]['field_key'] = $keyColumns[$fNode[$name]['field_name']];
            $fNode[$name]['node_after'] = (bool)($f['node_after'] ?? false);
            $fNode[$name]['node_location'] = $node;
        }

        // Group by processing priority (Option 2 refined)
        $groupFirstAny = [];
        $groupCustomBefore = [];
        $groupCustomAfter = [];
        $groupLastAfter = [];
        $groupLastBefore = [];

        foreach ($fNode as $key => $fdata) {
            $loc = $fdata['node_location'];
            $after = $fdata['node_after'];
            if ($loc === 'first') {
                $groupFirstAny[$key] = $fdata;
            } elseif ($loc === 'last') {
                if ($after) { $groupLastAfter[$key] = $fdata; } else { $groupLastBefore[$key] = $fdata; }
            } else {
                if ($after) { $groupCustomAfter[$key] = $fdata; } else { $groupCustomBefore[$key] = $fdata; }
            }
        }

        $insertedPositions = [];

        // 1) first (both before/after behave the same: insert at start, after number_lists if present)
        foreach ($groupFirstAny as $key => $fdata) {
            $idx = (int)$fdata['field_key'];
            $insertAt = $hasNumberLists ? $idx + 1 : $idx;
            self::arrayInsert($columns, $insertAt, $key);
            $insertedPositions[] = $insertAt;
        }

        // 2) last-after: if action exists insert at original last index (before action), else append to end
        $lastAfterNames = [];
        foreach ($groupLastAfter as $key => $fdata) {
            if ($hasAction) {
                $origLastIdx = (int)$fdata['field_key'];
                self::arrayInsert($columns, $origLastIdx, $key);
                $insertedPositions[] = $origLastIdx;
            } else {
                $columns[] = $key;
                $insertedPositions[] = array_key_last($columns);
            }
            $lastAfterNames[] = $key;
        }

        // 3) custom-before: insert at current index of target; if target is 'action' and we inserted any last-after, place after all last-after items
        foreach ($groupCustomBefore as $key => $fdata) {
            $target = $fdata['field_name'];
            $currIdx = array_search($target, $columns, true);
            if ($currIdx === false) { $currIdx = (int)$fdata['field_key']; }
            if ($target === 'action' && !empty($lastAfterNames)) {
                $currIdx = max(0, $currIdx - count($lastAfterNames));
            }
            self::arrayInsert($columns, $currIdx, $key);
            $insertedPositions[] = $currIdx;
        }

        // 4) custom-after at CURRENT index + 1 of target
        $hasAnyCustomAfter = count($groupCustomAfter) > 0;
        foreach ($groupCustomAfter as $key => $fdata) {
            $target = $fdata['field_name'];
            $currIdx = array_search($target, $columns, true);
            if ($currIdx === false) { $currIdx = (int)$fdata['field_key']; }
            self::arrayInsert($columns, $currIdx + 1, $key);
            $insertedPositions[] = $currIdx + 1;
        }

        // If any custom-after exists, move last-after items to the very end (keeps 'action' before them)
        if ($hasAnyCustomAfter && !empty($lastAfterNames)) {
            foreach ($lastAfterNames as $name) {
                $pos = array_search($name, $columns, true);
                if ($pos !== false) {
                    array_splice($columns, $pos, 1);
                    $columns[] = $name;
                }
            }
        }

        // 5) last-before: insert before last existing column
        foreach ($groupLastBefore as $key => $fdata) {
            $lastIndex = array_key_last($columns);
            $insertAt = max(0, $lastIndex);
            self::arrayInsert($columns, $insertAt, $key);
            $insertedPositions[] = $insertAt;
        }

        return $columns;
    }

    private static function arrayInsert(array &$array, int $position, $value): void
    {
        array_splice($array, max(0, $position), 0, [$value]);
    }
}