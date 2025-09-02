<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Columns;

/**
 * RelationsAndMetaShaper
 *
 * Extracted from legacy Objects::lists() to shape:
 * - labels (for relation display fields)
 * - columns meta: relations mapping and foreign_keys
 * - fields list (inserting relation display columns in place)
 *
 * Behavior is kept identical to legacy implementation.
 */
final class RelationsAndMetaShaper
{
    /**
     * @param  array  $fields Current columns list (by name)
     * @param  array  $fieldsetAdded Original user-specified fields before schema adjustments
     * @param  array  $relationalData Legacy $this->relational_data
     * @param  string  $tableName Current table name (for meta array indexing)
     * @param  array  $columnsMeta Reference to columns meta array for this table
     * @param  array  $labels Reference to labels array to be augmented
     * @return array Updated fields list
     */
    public static function apply(array $fields, array $fieldsetAdded, array $relationalData, string $tableName, array &$columnsMeta, array &$labels): array
    {
        $relations = [];
        $field_relations = [];
        $fieldset_changed = [];

        if (! empty($relationalData)) {
            foreach ($relationalData as $relData) {
                if (! empty($relData['field_target'])) {
                    foreach ($relData['field_target'] as $fr_name => $relation_fields) {
                        $field_relations[$fr_name] = $relation_fields;
                        if (in_array($fr_name, $fields, true)) {
                            $fieldset_changed[$fr_name] = $fr_name;
                        }
                    }
                }
                if (! empty($relData['foreign_keys'])) {
                    $columnsMeta['foreign_keys'] = $relData['foreign_keys'];
                }
            }
        }

        if (! empty($field_relations)) {
            // Compare against originally requested fields to detect diffs
            $checkFieldSet = array_diff($fieldsetAdded, $fields);
            if (! empty($fieldset_changed)) {
                $fieldsetChanged = [];
                foreach ($fields as $fid => $fval) {
                    if (! empty($fieldset_changed[$fval])) {
                        $fieldsetChanged[$fid] = $fieldset_changed[$fval];
                        unset($fields[$fid]);
                    }
                }
                // Use project helper for recursive distinct merge (legacy behavior)
                if (function_exists('array_merge_recursive_distinct')) {
                    $checkFieldSet = array_merge_recursive_distinct($checkFieldSet, $fieldsetChanged);
                } else {
                    // Fallback: emulate legacy behavior closely for flat structures
                    $checkFieldSet = $checkFieldSet + $fieldsetChanged;
                }
            }

            if (! empty($checkFieldSet)) {
                foreach ($checkFieldSet as $index => $field_diff) {
                    if (! empty($field_relations[$field_diff])) {
                        $relData = $field_relations[$field_diff];
                        $labels[$relData['field_name']] = $relData['field_label'];
                        $relations[$index] = $relData['field_name'];
                        $columnsMeta['relations'][$field_diff] = $relData;
                    }
                }
            }

            $refields = [];
            if (! empty($relations)) {
                foreach ($relations as $reid => $relation_name) {
                    // Use legacy helper to insert at same index
                    if (function_exists('canvastack_array_insert')) {
                        $refields = canvastack_array_insert($fields, $reid, $relation_name);
                    } else {
                        // Fallback: approximate insert without reindexing semantics
                        $before = array_slice($fields, 0, (int) $reid, true);
                        $after = array_slice($fields, (int) $reid, null, true);
                        $refields = array_merge($before, [$relation_name], $after);
                    }
                }
            }
            if (! empty($refields)) {
                $fields = $refields;
            }
        }

        return $fields;
    }
}
