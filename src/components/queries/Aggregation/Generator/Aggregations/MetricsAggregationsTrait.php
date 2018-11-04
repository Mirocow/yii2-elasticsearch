<?php
namespace mirocow\elasticsearch\components\queries\Aggregation\Generator\Aggregations;

/**
 * Trait MetricsAggregationsTrait
 *
 * The aggregations in this family compute metrics based on values extracted in one way or another from the documents that are being aggregated.
 * The values are typically extracted from the fields of the document (using the field data), but can also be generated using scripts.
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-metrics.html
 *
 * @package mirocow\elasticsearch\components\queries\Aggregation\Generator\Aggregations
 */
trait MetricsAggregationsTrait
{
    /**
     * Sum Aggregation
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-metrics-sum-aggregation.html
     */

    /**
     * @param $aggName
     * @param string $filterKey
     * @return mixed
     */
    public function getSumGenerator($aggName, $filterKey = 'Sum')
    {
        $generator = isset($this->generators['sum'])
            ? $this->generators['sum']
            : $this->generators['singleValueConstructor'];

        return $generator($aggName, $filterKey, 'value');
    }

    /**
     * @param $generator
     */
    public function setSumGenerator($generator)
    {
        $this->generators['sum'] = $generator;
    }
}