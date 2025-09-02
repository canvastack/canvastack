<?php

namespace Canvastack\Canvastack\Library\Components\Table\tests\Unit;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Columns\RelationsAndMetaShaper;
use PHPUnit\Framework\TestCase;

class RelationsAndMetaShaperTest extends TestCase
{
    public function test_apply_inserts_relation_at_correct_index_and_sets_labels_and_meta(): void
    {
        $fields = ['id', 'user_id', 'amount'];
        $fieldsetAdded = ['id', 'user_name', 'amount']; // requested alias

        $relationalData = [
            [
                'field_target' => [
                    // key is the requested alias in fieldset
                    'user_name' => [
                        'field_name' => 'users.name',
                        'field_label' => 'User Name',
                    ],
                ],
                'foreign_keys' => [
                    'user_id' => 'users.id',
                ],
            ],
        ];

        $columnsMeta = [];
        $labels = [];

        $outFields = RelationsAndMetaShaper::apply(
            $fields,
            $fieldsetAdded,
            $relationalData,
            'orders',
            $columnsMeta,
            $labels
        );

        // It should insert the relation display field at index 1, keeping original fields
        $this->assertSame(['id', 'users.name', 'user_id', 'amount'], array_values($outFields));

        // Labels mapping should be set for the relation display field
        $this->assertArrayHasKey('users.name', $labels);
        $this->assertSame('User Name', $labels['users.name']);

        // Columns meta should contain relations map and foreign_keys
        $this->assertArrayHasKey('relations', $columnsMeta);
        $this->assertArrayHasKey('user_name', $columnsMeta['relations']);
        $this->assertSame('users.name', $columnsMeta['relations']['user_name']['field_name']);

        $this->assertArrayHasKey('foreign_keys', $columnsMeta);
        $this->assertSame(['user_id' => 'users.id'], $columnsMeta['foreign_keys']);
    }

    public function test_apply_no_relational_data_returns_same_fields_and_no_meta(): void
    {
        $fields = ['id', 'title'];
        $fieldsetAdded = ['id', 'title'];

        $columnsMeta = [];
        $labels = [];

        $outFields = RelationsAndMetaShaper::apply(
            $fields,
            $fieldsetAdded,
            [],
            'posts',
            $columnsMeta,
            $labels
        );

        $this->assertSame($fields, $outFields);
        $this->assertSame([], $labels);
        $this->assertSame([], $columnsMeta);
    }
}
