<?php
namespace mirocow\elasticsearch\components\queries\Aggregation\Generator;

interface AggGeneratorInterface
{
    public function getFilterGenerator($aggName, $filterKey = '');

    public function getFiltersGenerator($aggName);

    public function getTermsGenerator($aggName);

    public function getDateHistogramGenerator($aggName, $keyAsString = true);

    public function getRangeGenerator($aggName);

    public function getSumGenerator($aggName, $filterKey = 'Sum');

    public function getNestedGenerator($aggName);

    public function getReverseNestedGenerator($aggName);
}
