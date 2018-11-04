<?php
namespace mirocow\elasticsearch\components\queries\Aggregation\Generator;

use mirocow\elasticsearch\components\queries\Aggregation\AggResult;
use mirocow\elasticsearch\components\queries\Aggregation\Generator\Aggregations\AggregationMetadataTrait;
use mirocow\elasticsearch\components\queries\Aggregation\Generator\Aggregations\BucketAggregationsTrait;
use mirocow\elasticsearch\components\queries\Aggregation\Generator\Aggregations\MetricsAggregationsTrait;
use mirocow\elasticsearch\components\queries\Aggregation\Generator\Aggregations\PipelineAggregationsTrait;

/**
 * Class DefaultAggGenerator
 * @package mirocow\elasticsearch\components\queries\helpers\Aggregation\Generator
 */
class DefaultAggGenerator implements AggGeneratorInterface
{
    protected $generators = [];

    use BucketAggregationsTrait;

    use MetricsAggregationsTrait;

    use PipelineAggregationsTrait;

    use AggregationMetadataTrait;

    /**
     * BucketAggregationsGenerator constructor.
     * @param array $generators
     */
    public function __construct($generators = [])
    {
        if (!isset($this->generators['singleValueConstructor'])) {
            $this->generators['singleValueConstructor'] = function ($aggName, $key = '', $valueField = 'doc_count') {
                return self::singleValueConstructor($aggName, $key, $valueField);
            };
        }

        if (!isset($this->generators['keyedBucketConstructor'])) {
            $this->generators['keyedBucketConstructor'] = function ($aggName, $valueField = 'doc_count') {
                return self::keyedBucketConstructor($aggName, $valueField);
            };
        }

        if (!isset($this->generators['bucketConstructor'])) {
            $this->generators['bucketConstructor'] = function ($aggName) {
                return self::bucketConstructor($aggName);
            };
        }
    }

    /**
     * @param string $aggName
     * @param string $key
     * @param string $valueField
     * @return callable
     */
    public static function singleValueConstructor($aggName, $key = '', $valueField = 'doc_count')
    {
        if ($key === '') {
            $generator = function ($results) use ($aggName, $valueField) {
                yield new AggResult($results[$aggName][$valueField], $results[$aggName]);
            };
        } else {
            $generator = function ($results) use ($aggName, $key, $valueField) {
                yield $key => new AggResult($results[$aggName][$valueField], $results[$aggName]);
            };
        }

        return $generator;
    }

    /**
     * @param string $aggName
     * @return callable
     */
    public static function bucketConstructor($aggName)
    {
        return $generator = function ($results) use ($aggName) {

            /**
             * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/ml-results-resource.html#ml-results-buckets
             */
            if (isset($results[$aggName]['buckets'])) {
                foreach ($results[$aggName]['buckets'] as $bucket) {
                    yield $bucket['key'] => new AggResult($bucket['doc_count'], $bucket);
                }
            }

            /**
             * top_hits, etc
             * @see
             */
            if (isset($results[$aggName]['hits']['hits'])) {
                yield $aggName => new AggResult($results[$aggName], $results[$aggName]);
            }

            /**
             * The number of documents that have at least one term for this field, or -1 if this measurement isn’t available on one or more shards.
             */
            if (isset($results[$aggName]['doc_count'])) {
                yield $aggName => new AggResult($results, $results[$aggName]);
            }

        };
    }

    /**
     * @param string $aggName
     * @param string $valueField
     * @return callable
     */
    public static function keyedBucketConstructor($aggName, $valueField = 'doc_count')
    {
        $generator = function ($results) use ($aggName, $valueField) {
            foreach ($results[$aggName]['buckets'] as $key => $bucket) {
                yield $key => new AggResult($bucket[$valueField], $bucket);
            }
        };

        return $generator;
    }

}
