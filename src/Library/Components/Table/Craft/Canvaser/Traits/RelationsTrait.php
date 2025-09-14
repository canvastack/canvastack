<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Utils\RelationshipIntrospector;

/**
 * RelationsTrait
 *
 * Encapsulates relational data collection and field replacement utilities.
 */
trait RelationsTrait
{
    /**
     * Storage for pre-resolved relational data used in label/value replacement.
     */
    public $relational_data = [];

    private function relation_draw($relation, $relation_function, $fieldname, $label)
    {
        if (! empty($relation->{$relation_function})) {
            $dataRelate = $relation->{$relation_function}->getAttributes();
            $relateKEY = intval($relation['id']);
        } else {
            $dataRelate = $relation->getAttributes();
            $relateKEY = intval($dataRelate['id']);
        }

        $fieldReplacement = null;
        if (canvastack_string_contained($fieldname, '::')) {
            $fieldsplit = explode('::', $fieldname);
            $fieldReplacement = $fieldsplit[0];
            $fieldname = $fieldsplit[1];
            $data_relation = $dataRelate[$fieldname];
            $data_value = $dataRelate[$fieldname];
        } else {
            $data_relation = $dataRelate[$fieldname];
            $data_value = $dataRelate[$fieldname];
        }

        if (! empty($data_relation)) {
            $fieldset = $fieldname;
            if (! is_empty($fieldReplacement)) {
                $fieldset = $fieldReplacement;
            }

            $this->relational_data[$relation_function]['field_target'][$fieldset]['field_name'] = $fieldset;
            $this->relational_data[$relation_function]['field_target'][$fieldset]['field_label'] = $label;

            if (! empty($relation->pivot)) {
                foreach ($relation->pivot->getAttributes() as $pivot_field => $pivot_data) {
                    $this->relational_data[$relation_function]['field_target'][$fieldset]['relation_data'][$relateKEY][$pivot_field] = $pivot_data;
                }
            }

            $this->relational_data[$relation_function]['field_target'][$fieldset]['relation_data'][$relateKEY]['field_value'] = $data_value;
        }
    }

    private function relationship($model, $relation_function, $field_display, $filter_foreign_keys = [], $label = null, $field_connect = null)
    {
        if (! empty($model->with($relation_function)->get())) {
            $relational_data = $model->with($relation_function)->get();
            if (empty($label)) {
                $label = ucwords(canvastack_clean_strings($field_display, ' '));
            }

            foreach ($relational_data as $item) {
                if (! empty($item->{$relation_function})) {
                    if (canvastack_is_collection($item->{$relation_function})) {
                        foreach ($item->{$relation_function} as $relation) {
                            $this->relation_draw($relation, $relation_function, $field_display, $label);
                        }
                    } else {
                        $this->relation_draw($item, $relation_function, "{$field_connect}::{$field_display}", $label);
                    }
                }
            }

            if (! empty($filter_foreign_keys)) {
                $this->relational_data[$relation_function]['foreign_keys'] = $filter_foreign_keys;
            }
        }
    }

    public function relations($model, $relation_function, $field_display, $filter_foreign_keys = [], $label = null)
    {
        // Auto-detect foreign keys if not provided
        if (empty($filter_foreign_keys)) {
            $analysis = RelationshipIntrospector::analyzeRelationship($model, $relation_function);
            
            if ($analysis['success']) {
                $filter_foreign_keys = $analysis['foreign_keys'];
                
                // Log the auto-detected foreign keys for debugging
                \Log::info("RelationsTrait: Auto-detected foreign keys for {$relation_function}", [
                    'relation_type' => $analysis['relation_type'],
                    'foreign_keys' => $filter_foreign_keys,
                    'tables' => $analysis['tables'],
                    'pivot_table' => $analysis['pivot_table'] ?? null
                ]);
            } else {
                \Log::warning("RelationsTrait: Failed to auto-detect foreign keys for {$relation_function}", [
                    'error' => $analysis['error']
                ]);
            }
        }
        
        return $this->relationship($model, $relation_function, $field_display, $filter_foreign_keys, $label, null);
    }

    public function fieldReplacementValue($model, $relation_function, $field_display, $label = null, $field_connect = null)
    {
        return $this->relationship($model, $relation_function, $field_display, [], $label, $field_connect);
    }
}
