<?php
namespace mirocow\elasticsearch\components\queries\helpers;

/**
 * Class AggQueryHelper
 * @package mirocow\elasticsearch\components\queries\helpers
 */
class AggQueryHelper
{
    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-bucket-filter-aggregation.html
     * @param array $query
     * @param string $aggName
     * @return array
     */
    public static function filter($query, $aggName = 'filter_agg') :array
    {
        return [
            $aggName => [
                'filter' => $query
            ]
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-bucket-filters-aggregation.html
     * @param array $queries
     * @param string $aggName
     * @return array
     */
    public static function filters($queries, $aggName = 'filters_agg') :array
    {
        $terms = [];

        if(!is_array($queries)){
            $terms[] = $queries;
        } else {
            $terms = $queries;
        }

        return [
            $aggName => [
                'filters' => [
                    'filters' => $terms,
                ]
            ]
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-bucket-terms-aggregation.html
     * @param string $field
     * @param array $termsOptions
     * @param string $aggName
     * @return array
     */
    public static function terms($field, $termsOptions = [], $aggName = 'terms_agg') :array
    {
        if($field) {
            $termsOptions['field'] = $field;
        }
        return [
            $aggName => [
                'terms' => $termsOptions
            ]
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-bucket-datehistogram-aggregation.html
     * @param string $field
     * @param array $dateHistogramOptions
     * @param string $aggName
     * @return array
     */
    public static function dateHistogram($field, $dateHistogramOptions = [], $aggName = 'date_histogram_agg') :array
    {
        $dateHistogramOptions['field'] = $field;
        return [
            $aggName => [
                'date_histogram' => $dateHistogramOptions
            ]
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-bucket-range-aggregation.html
     * @param string $field
     * @param array $ranges
     * @param array $rangeOptions
     * @param string $aggName
     * @return array
     */
    public static function range($field, $ranges, $rangeOptions = [], $aggName = 'range_agg') :array
    {
        $rangeOptions['field'] = $field;
        $rangeOptions['ranges'] = $ranges;
        return [
            $aggName => [
                'range' => $rangeOptions
            ]
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-metrics-sum-aggregation.html
     * @param string $field
     * @param string $aggName
     * @return array
     */
    public static function sum($field, $aggName = 'sum_agg') :array
    {
        return [
            $aggName => [
                'sum' => [
                    'field' => $field
                ]
            ]
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-metrics-min-aggregation.html
     * @param string $field
     * @param string $aggName
     * @return array
     */
    public static function min($field, $aggName = 'min_agg') :array
    {
        return [
            $aggName => [
                'min' => [
                    'field' => $field
                ]
            ]
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-metrics-max-aggregation.html
     * @param string $field
     * @param string $aggName
     * @return array
     */
    public static function max($field, $aggName = 'max_agg') :array
    {
        return [
            $aggName => [
                'max' => [
                    'field' => $field
                ]
            ]
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-bucket-nested-aggregation.html
     * @param string $path
     * @param string $aggName
     * @return array
     */
    public static function nested($path, $aggName = 'nested_agg') :array
    {
        return [
            $aggName => [
                'nested' => [
                    'path' => $path
                ]
            ]
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-bucket-reverse-nested-aggregation.html
     * @param string $aggName
     * @return array
     */
    public static function reverseNested($aggName = 'reverse_nested_agg') :array
    {
        return [
            $aggName => [
                'reverse_nested' => (object)[]
            ]
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations.html
     * @param $method
     * @param $aggregationsOptions
     * @param string $aggName
     * @return array
     */
    public static function aggs($method, $aggregationsOptions, $aggName = 'aggs') :array
    {
        return [
            $aggName => [
                $method => $aggregationsOptions,
            ]
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-bucket-global-aggregation.html
     */
    public function global($aggName = 'all_products') :array
    {
        return [
            $aggName => [
                'global' => (object)[],
            ]
        ];
    }
}
