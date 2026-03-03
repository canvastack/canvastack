<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Filter;

use Canvastack\Canvastack\Components\Table\Filter\Filter;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for Filter class.
 *
 * Tests filter configuration, options management, value management,
 * related filters logic, and auto-submit detection.
 */
class FilterTest extends TestCase
{
    /**
     * Test that Filter can be instantiated.
     */
    public function test_filter_can_be_instantiated(): void
    {
        $filter = new Filter('status', 'selectbox');

        $this->assertInstanceOf(Filter::class, $filter);
    }

    /**
     * Test that getColumn() returns column name.
     */
    public function test_get_column_returns_column_name(): void
    {
        $filter = new Filter('status', 'selectbox');

        $this->assertEquals('status', $filter->getColumn());
    }

    /**
     * Test that getType() returns filter type.
     */
    public function test_get_type_returns_filter_type(): void
    {
        $filter = new Filter('status', 'selectbox');

        $this->assertEquals('selectbox', $filter->getType());
    }

    /**
     * Test that getRelate() returns relate configuration.
     */
    public function test_get_relate_returns_relate_config(): void
    {
        $filter = new Filter('status', 'selectbox', true);

        $this->assertTrue($filter->getRelate());
    }

    /**
     * Test that setOptions() and getOptions() work.
     */
    public function test_set_and_get_options(): void
    {
        $filter = new Filter('status', 'selectbox');
        $options = [
            ['value' => 'active', 'label' => 'Active'],
            ['value' => 'inactive', 'label' => 'Inactive'],
        ];

        $filter->setOptions($options);

        $this->assertEquals($options, $filter->getOptions());
    }

    /**
     * Test that setValue() and getValue() work.
     */
    public function test_set_and_get_value(): void
    {
        $filter = new Filter('status', 'selectbox');

        $filter->setValue('active');

        $this->assertEquals('active', $filter->getValue());
    }

    /**
     * Test that setLabel() and getLabel() work.
     */
    public function test_set_and_get_label(): void
    {
        $filter = new Filter('status', 'selectbox');

        $filter->setLabel('Status Filter');

        $this->assertEquals('Status Filter', $filter->getLabel());
    }

    /**
     * Test that default label is generated from column name.
     */
    public function test_default_label_generated_from_column(): void
    {
        $filter = new Filter('user_status', 'selectbox');

        $this->assertEquals('User Status', $filter->getLabel());
    }

    /**
     * Test that setAutoSubmit() and shouldAutoSubmit() work.
     */
    public function test_set_and_check_auto_submit(): void
    {
        $filter = new Filter('status', 'selectbox');

        $this->assertFalse($filter->shouldAutoSubmit());

        $filter->setAutoSubmit(true);

        $this->assertTrue($filter->shouldAutoSubmit());
    }

    /**
     * Test that getRelatedFilters() returns empty array when relate is false.
     */
    public function test_get_related_filters_returns_empty_when_false(): void
    {
        $filter = new Filter('status', 'selectbox', false);

        $this->assertEmpty($filter->getRelatedFilters());
    }

    /**
     * Test that getRelatedFilters() returns empty array when relate is true.
     */
    public function test_get_related_filters_returns_empty_when_true(): void
    {
        $filter = new Filter('status', 'selectbox', true);

        // True means cascade to all filters after this one
        // This will be handled by FilterManager
        $this->assertEmpty($filter->getRelatedFilters());
    }

    /**
     * Test that getRelatedFilters() returns single column when relate is string.
     */
    public function test_get_related_filters_returns_single_column(): void
    {
        $filter = new Filter('region', 'selectbox', 'cluster');

        $this->assertEquals(['cluster'], $filter->getRelatedFilters());
    }

    /**
     * Test that getRelatedFilters() returns multiple columns when relate is array.
     */
    public function test_get_related_filters_returns_multiple_columns(): void
    {
        $filter = new Filter('region', 'selectbox', ['cluster', 'sub_cluster']);

        $this->assertEquals(['cluster', 'sub_cluster'], $filter->getRelatedFilters());
    }

    /**
     * Test that hasCascading() returns false when relate is false.
     */
    public function test_has_cascading_returns_false(): void
    {
        $filter = new Filter('status', 'selectbox', false);

        $this->assertFalse($filter->hasCascading());
    }

    /**
     * Test that hasCascading() returns true when relate is true.
     */
    public function test_has_cascading_returns_true(): void
    {
        $filter = new Filter('region', 'selectbox', true);

        $this->assertTrue($filter->hasCascading());
    }

    /**
     * Test that hasCascading() returns true when relate is string.
     */
    public function test_has_cascading_returns_true_for_string(): void
    {
        $filter = new Filter('region', 'selectbox', 'cluster');

        $this->assertTrue($filter->hasCascading());
    }

    /**
     * Test that hasCascading() returns true when relate is array.
     */
    public function test_has_cascading_returns_true_for_array(): void
    {
        $filter = new Filter('region', 'selectbox', ['cluster', 'sub_cluster']);

        $this->assertTrue($filter->hasCascading());
    }

    /**
     * Test that cascadesToAll() returns true only when relate is true.
     */
    public function test_cascades_to_all_returns_true_only_for_true(): void
    {
        $filterTrue = new Filter('region', 'selectbox', true);
        $filterFalse = new Filter('status', 'selectbox', false);
        $filterString = new Filter('region', 'selectbox', 'cluster');
        $filterArray = new Filter('region', 'selectbox', ['cluster']);

        $this->assertTrue($filterTrue->cascadesToAll());
        $this->assertFalse($filterFalse->cascadesToAll());
        $this->assertFalse($filterString->cascadesToAll());
        $this->assertFalse($filterArray->cascadesToAll());
    }

    /**
     * Test that hasValue() returns false when value is null.
     */
    public function test_has_value_returns_false_for_null(): void
    {
        $filter = new Filter('status', 'selectbox');

        $this->assertFalse($filter->hasValue());
    }

    /**
     * Test that hasValue() returns false when value is empty string.
     */
    public function test_has_value_returns_false_for_empty_string(): void
    {
        $filter = new Filter('status', 'selectbox');
        $filter->setValue('');

        $this->assertFalse($filter->hasValue());
    }

    /**
     * Test that hasValue() returns true when value is set.
     */
    public function test_has_value_returns_true_when_set(): void
    {
        $filter = new Filter('status', 'selectbox');
        $filter->setValue('active');

        $this->assertTrue($filter->hasValue());
    }

    /**
     * Test that hasValue() returns true for zero value.
     */
    public function test_has_value_returns_true_for_zero(): void
    {
        $filter = new Filter('count', 'inputbox');
        $filter->setValue(0);

        $this->assertTrue($filter->hasValue());
    }

    /**
     * Test that toArray() returns complete filter data.
     */
    public function test_to_array_returns_complete_data(): void
    {
        $filter = new Filter('status', 'selectbox', true);
        $filter->setLabel('Status Filter');
        $filter->setValue('active');
        $filter->setOptions([
            ['value' => 'active', 'label' => 'Active'],
            ['value' => 'inactive', 'label' => 'Inactive'],
        ]);
        $filter->setAutoSubmit(true);

        $array = $filter->toArray();

        $this->assertEquals('status', $array['column']);
        $this->assertEquals('selectbox', $array['type']);
        $this->assertEquals('Status Filter', $array['label']);
        $this->assertEquals('active', $array['value']);
        $this->assertCount(2, $array['options']);
        $this->assertTrue($array['relate']);
        $this->assertTrue($array['autoSubmit']);
    }

    /**
     * Test that toArray() includes default values.
     */
    public function test_to_array_includes_defaults(): void
    {
        $filter = new Filter('status', 'selectbox');

        $array = $filter->toArray();

        $this->assertNull($array['value']);
        $this->assertEmpty($array['options']);
        $this->assertFalse($array['relate']);
        $this->assertFalse($array['autoSubmit']);
    }

    /**
     * Test filter with inputbox type.
     */
    public function test_filter_with_inputbox_type(): void
    {
        $filter = new Filter('name', 'inputbox');

        $this->assertEquals('inputbox', $filter->getType());
        $this->assertEquals('Name', $filter->getLabel());
    }

    /**
     * Test filter with datebox type.
     */
    public function test_filter_with_datebox_type(): void
    {
        $filter = new Filter('created_at', 'datebox');

        $this->assertEquals('datebox', $filter->getType());
        $this->assertEquals('Created At', $filter->getLabel());
    }

    /**
     * Test cascading filter scenario (Keren Pro example).
     */
    public function test_cascading_filter_scenario(): void
    {
        // period → cor → region → cluster
        $periodFilter = new Filter('period_string', 'selectbox', true);
        $corFilter = new Filter('cor', 'selectbox', true);
        $regionFilter = new Filter('region', 'selectbox', true);
        $clusterFilter = new Filter('cluster', 'selectbox', false);

        $periodFilter->setAutoSubmit(true);
        $corFilter->setAutoSubmit(true);
        $regionFilter->setAutoSubmit(true);

        $this->assertTrue($periodFilter->hasCascading());
        $this->assertTrue($periodFilter->cascadesToAll());
        $this->assertTrue($periodFilter->shouldAutoSubmit());

        $this->assertTrue($corFilter->hasCascading());
        $this->assertTrue($corFilter->cascadesToAll());
        $this->assertTrue($corFilter->shouldAutoSubmit());

        $this->assertTrue($regionFilter->hasCascading());
        $this->assertTrue($regionFilter->cascadesToAll());
        $this->assertTrue($regionFilter->shouldAutoSubmit());

        $this->assertFalse($clusterFilter->hasCascading());
        $this->assertFalse($clusterFilter->cascadesToAll());
        $this->assertFalse($clusterFilter->shouldAutoSubmit());
    }

    /**
     * Test specific related filters scenario.
     */
    public function test_specific_related_filters_scenario(): void
    {
        // region relates to both cluster and sub_cluster
        $filter = new Filter('region', 'selectbox', ['cluster', 'sub_cluster']);

        $relatedFilters = $filter->getRelatedFilters();

        $this->assertCount(2, $relatedFilters);
        $this->assertContains('cluster', $relatedFilters);
        $this->assertContains('sub_cluster', $relatedFilters);
    }

    /**
     * Test filter value persistence.
     */
    public function test_filter_value_persistence(): void
    {
        $filter = new Filter('status', 'selectbox');

        $filter->setValue('active');
        $this->assertEquals('active', $filter->getValue());

        $filter->setValue('inactive');
        $this->assertEquals('inactive', $filter->getValue());

        $filter->setValue(null);
        $this->assertNull($filter->getValue());
    }

    /**
     * Test filter options update.
     */
    public function test_filter_options_update(): void
    {
        $filter = new Filter('region', 'selectbox');

        $initialOptions = [
            ['value' => 'region1', 'label' => 'Region 1'],
        ];
        $filter->setOptions($initialOptions);
        $this->assertCount(1, $filter->getOptions());

        $updatedOptions = [
            ['value' => 'region1', 'label' => 'Region 1'],
            ['value' => 'region2', 'label' => 'Region 2'],
        ];
        $filter->setOptions($updatedOptions);
        $this->assertCount(2, $filter->getOptions());
    }
}
