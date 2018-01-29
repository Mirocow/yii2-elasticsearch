<?php
namespace common\modules\elasticsearch\components\queries\helpers;

class AggQueryHelper
{
    /**
     * @param array $query
     * @param string $aggName
     * @return array
     */
    public static function filter($query, $aggName = 'filter_agg')
    {
        return [
            $aggName => [
                'filter' => $query
            ]
        ];
    }

    /**
     * @param array $queries
     * @param string $aggName
     * @return array
     */
    public static function filters($queries, $aggName = 'filters_agg')
    {
        return [
            $aggName => [
                'filters' => [
                    'filters' => $queries,
                ]
            ]
        ];
    }

    /**
     * @param string $field
     * @param array $termsOptions
     * @param string $aggName
     * @return array
     */
    public static function terms($field, $termsOptions = [], $aggName = 'terms_agg')
    {
        $termsOptions['field'] = $field;
        return [
            $aggName => [
                'terms' => $termsOptions
            ]
        ];
    }

    /**
     * @param string $field
     * @param array $dateHistogramOptions
     * @param string $aggName
     * @return array
     */
    public static function dateHistogram($field, $dateHistogramOptions = [], $aggName = 'date_histogram_agg')
    {
        $dateHistogramOptions['field'] = $field;
        return [
            $aggName => [
                'date_histogram' => $dateHistogramOptions
            ]
        ];
    }

    /**
     * @param string $field
     * @param array $ranges
     * @param array $rangeOptions
     * @param string $aggName
     * @return array
     */
    public static function range($field, $ranges, $rangeOptions = [], $aggName = 'range_agg')
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
     * @param string $field
     * @param string $aggName
     * @return array
     */
    public static function sum($field, $aggName = 'sum_agg')
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
     * @param string $path
     * @param string $aggName
     * @return array
     */
    public static function nested($path, $aggName = 'nested_agg')
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
     * @param string $aggName
     * @return array
     */
    public static function reverseNested($aggName = 'reverse_nested_agg')
    {
        return [
            $aggName => [
                'reverse_nested' => (object)[]
            ]
        ];
    }

    /**
     * @param $method
     * @param $aggregationsOptions
     * @param string $aggName
     * @return array
     */
    public static function aggs($method, $aggregationsOptions, $aggName = 'aggs')
    {
        return [
            $aggName => [
                $method => $aggregationsOptions,
            ]
        ];
    }
}
