<?php
namespace mirocow\elasticsearch\components\queries;

use mirocow\elasticsearch\components\queries\helpers\AggQueryHelper;
use mirocow\elasticsearch\components\queries\Aggregation\Aggregation;
use mirocow\elasticsearch\components\queries\Aggregation\AggregationInterface;
use mirocow\elasticsearch\components\queries\Aggregation\AggregationMulti;
use mirocow\elasticsearch\components\queries\Aggregation\AggResult;
use mirocow\elasticsearch\components\queries\Aggregation\Generator\AggGeneratorInterface;
use mirocow\elasticsearch\components\queries\Aggregation\Generator\DefaultAggGenerator;

/**
 * Class AggBuilder
 * @package mirocow\elasticsearch\components\queries
 */
class AggBuilder
{
    /** @var AggGeneratorInterface */
    protected $aggGenerator;

    public function __construct(AggGeneratorInterface $aggGenerator = null)
    {
        if ($aggGenerator === null) {
            $aggGenerator = new DefaultAggGenerator();
        }
        $this->aggGenerator = $aggGenerator;
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-bucket-filter-aggregation.html
     * @param string $field
     * @param array $query
     * @param Aggregation $nestedAgg
     * @param string $aggName
     * @param string|null $filterKey
     * @return Aggregation
     */
    public function filter($field, $query, $nestedAgg = null, $aggName = '', $filterKey = '')
    {
        if(!$aggName) {
            $aggName = "{$field}_filter_agg";
        }
        return new Aggregation(
            AggQueryHelper::filter($query, $aggName),
            $this->aggGenerator->getFilterGenerator($aggName, $filterKey),
            $nestedAgg
        );
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-bucket-filters-aggregation.html
     * @param string $field
     * @param array $queries
     * @param Aggregation $nestedAgg
     * @param string $aggName
     * @return Aggregation
     */
    public function filters($field, $queries, $nestedAgg = null, $aggName = '')
    {
        if(!$aggName) {
            $aggName = "{$field}_filters_agg";
        }
        return new Aggregation(
            AggQueryHelper::filters($queries, $aggName),
            $this->aggGenerator->getFiltersGenerator($aggName),
            $nestedAgg
        );
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-bucket-terms-aggregation.html
     * @param string $field
     * @param array $termsOptions
     * @param Aggregation $nestedAgg
     * @param string $aggName
     * @return Aggregation
     */
    public function terms($field, $termsOptions = [], $nestedAgg = null, $aggName = '')
    {
        if(!$aggName) {
            $aggName = "{$field}_terms_agg";
        }
        return new Aggregation(
            AggQueryHelper::terms($field, $termsOptions, $aggName),
            $this->aggGenerator->getTermsGenerator($aggName),
            $nestedAgg
        );
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations.html
     * @param $method
     * @param array $aggregationsOptions
     * @param null $nestedAgg
     * @param string $aggName
     * @return Aggregation
     */
    public function aggs($method, $aggregationsOptions = [], $nestedAgg = null, $aggName = '')
    {
        if(!$aggName) {
            $aggName = "{$method}_aggs";
        }
        return new Aggregation(
            AggQueryHelper::aggs($method, $aggregationsOptions, $aggName),
            $this->aggGenerator->getAggregationsGenerator($aggName),
            $nestedAgg
        );
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-bucket-global-aggregation.html
     * @param $method
     * @param array $aggregationsOptions
     * @param null $nestedAgg
     * @param string $aggName
     * @return Aggregation
     */
    public function global($method, $aggregationsOptions = [], $nestedAgg = null, $aggName = '')
    {
        if(!$aggName) {
            $aggName = "{$method}_aggs";
        }
        return new Aggregation(
            AggQueryHelper::global($aggName),
            $this->aggGenerator->getAggregationsGenerator($aggName),
            $nestedAgg
        );
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-metrics-top-hits-aggregation.html#_top_hits_support_in_a_nested_or_reverse_nested_aggregator
     * @param array $aggregationsOptions
     * @param null $nestedAgg
     * @return Aggregation
     */
    public function topHits($aggregationsOptions = [], $nestedAgg = null)
    {
        return  $this->aggs('top_hits', $aggregationsOptions, $nestedAgg);
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-bucket-datehistogram-aggregation.html
     * @param string $field
     * @param array $dateHistogramOptions
     * @param Aggregation $nestedAgg
     * @param string $aggName
     * @param bool $keyAsString
     * @return Aggregation
     */
    public function dateHistogram($field, $dateHistogramOptions = [], $nestedAgg = null, $aggName = '', $keyAsString = true)
    {
        if(!$aggName) {
            $aggName = "{$field}_date_histogram_agg";
        }
        return new Aggregation(
            AggQueryHelper::dateHistogram($field, $dateHistogramOptions, $aggName),
            $this->aggGenerator->getDateHistogramGenerator($aggName, $keyAsString),
            $nestedAgg
        );
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-bucket-range-aggregation.html
     * @param string $field
     * @param array $ranges
     * @param array $rangeOptions
     * @param Aggregation $nestedAgg
     * @param string $aggName
     * @return Aggregation
     */
    public function range($field, $ranges, $rangeOptions = [], $nestedAgg = null, $aggName = '')
    {
        if(!$aggName) {
            $aggName = "{$field}_range_agg";
        }
        return new Aggregation(
            AggQueryHelper::range($field, $ranges, $rangeOptions, $aggName),
            $this->aggGenerator->getRangeGenerator($aggName),
            $nestedAgg
        );
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-metrics-sum-aggregation.html
     * @param string $field
     * @param Aggregation $nestedAgg
     * @param string $aggName
     * @param string $filterKey
     * @return Aggregation
     */
    public function sum($field, $nestedAgg = null, $aggName = '', $filterKey = 'Sum')
    {
        if(!$aggName) {
            $aggName = "{$field}_sum_agg";
        }
        return new Aggregation(
            AggQueryHelper::sum($field, $aggName),
            $this->aggGenerator->getSumGenerator($aggName, $filterKey),
            $nestedAgg
        );
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-metrics-min-aggregation.html
     * @param string $field
     * @param Aggregation $nestedAgg
     * @param string $aggName
     * @param string $filterKey
     * @return Aggregation
     */
    public function min($field, $nestedAgg = null, $aggName = '', $filterKey = 'Min')
    {
        if(!$aggName) {
            $aggName = "{$field}_min_agg";
        }
        return new Aggregation(
            AggQueryHelper::min($field, $aggName),
            $this->aggGenerator->getSumGenerator($aggName, $filterKey),
            $nestedAgg
        );
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-metrics-max-aggregation.html
     * @param string $field
     * @param Aggregation $nestedAgg
     * @param string $aggName
     * @param string $filterKey
     * @return Aggregation
     */
    public function max($field, $nestedAgg = null, $aggName = '', $filterKey = 'Max')
    {
        if(!$aggName) {
            $aggName = "{$field}_max_agg";
        }
        return new Aggregation(
            AggQueryHelper::max($field, $aggName),
            $this->aggGenerator->getSumGenerator($aggName, $filterKey),
            $nestedAgg
        );
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-bucket-nested-aggregation.html
     * @param string $path
     * @param array $nestedAgg
     * @param string $aggName
     * @return Aggregation
     */
    public function nested($path, $nestedAgg = null, $aggName = '')
    {
        if(!$aggName) {
            $aggName = "{$path}_nested_agg";
        }
        return new Aggregation(
            AggQueryHelper::nested($path, $aggName),
            $this->aggGenerator->getNestedGenerator($aggName),
            $nestedAgg
        );
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-bucket-reverse-nested-aggregation.html
     * @param Aggregation $nestedAgg
     * @param string $aggName
     * @return Aggregation
     */
    public function reverseNested($nestedAgg = null, $aggName = '')
    {
        if(!$aggName) {
            $aggName = "reverse_nested_agg";
        }
        return new Aggregation(
            AggQueryHelper::reverseNested($aggName),
            $this->aggGenerator->getReverseNestedGenerator($aggName),
            $nestedAgg
        );
    }

    /**
     * @param AggregationInterface[] $aggs
     * @return AggregationMulti
     */
    public function multi($aggs)
    {
        return new AggregationMulti($aggs);
    }

    /**
     * @return Aggregation
     */
    public static function make()
    {
        return new Aggregation([], self::emptyGenerator());
    }

    /**
     * @return \Closure
     */
    public static function emptyGenerator()
    {
        return function ($results) {
            yield new AggResult(0, $results);
        };
    }

}
