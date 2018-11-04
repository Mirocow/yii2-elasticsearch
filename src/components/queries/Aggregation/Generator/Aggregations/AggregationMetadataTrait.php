<?php
namespace mirocow\elasticsearch\components\queries\Aggregation\Generator\Aggregations;

/**
 * Trait AggregationMetadataTrait
 * You can associate a piece of metadata with individual aggregations at request time that will be returned in place at response time.
 * Consider this example where we want to associate the color blue with our terms aggregation.
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/agg-metadata.html
 *
 * @package mirocow\elasticsearch\components\queries\Aggregation\Generator\Aggregations
 */
trait AggregationMetadataTrait
{
    /**
     * @param $aggName
     * @return mixed
     */
    public function getAggregationsGenerator($aggName)
    {
        $generator = isset($this->generators['aggs'])
            ? $this->generators['aggs']
            : $this->generators['bucketConstructor'];

        return $generator($aggName);
    }

    /**
     * @param $generator
     */
    public function setAggregationsGenerator($generator)
    {
        $this->generators['aggs'] = $generator;
    }
}