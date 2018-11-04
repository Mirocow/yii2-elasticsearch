<?php
namespace mirocow\elasticsearch\components\queries\Aggregation\Generator\Aggregations;

/**
 * Trait BucketAggregations
 * Bucket aggregations don’t calculate metrics over fields like the metrics aggregations do, but instead, they create buckets of documents.
 * Each bucket is associated with a criterion (depending on the aggregation type) which determines whether or not a document in the current context "falls" into it.
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-bucket.html
 *
 * @package mirocow\elasticsearch\components\queries\Aggregation\Generator\Aggregations
 */
trait BucketAggregationsTrait
{
    /**
     * Range Aggregation
     */

    /**
     * @param $aggName
     * @return mixed
     */
    public function getRangeGenerator($aggName)
    {
        $generator = isset($this->generators['range'])
            ? $this->generators['range']
            : $this->generators['bucketConstructor'];

        return $generator($aggName);
    }

    /**
     * @param $generator
     */
    public function setRangeGenerator($generator)
    {
        $this->generators['range'] = $generator;
    }

    /**
     * Nested Aggregation
     */

    /**
     * @param $aggName
     * @return mixed
     */
    public function getNestedGenerator($aggName)
    {
        $generator = isset($this->generators['nested'])
            ? $this->generators['nested']
            : $this->generators['singleValueConstructor'];

        return $generator($aggName);
    }

    /**
     * @param $generator
     */
    public function setNestedGenerator($generator)
    {
        $this->generators['nested'] = $generator;
    }

    /**
     * Reverse nested Aggregation
     */

    /**
     * @param $aggName
     * @return mixed
     */
    public function getReverseNestedGenerator($aggName)
    {
        $generator = isset($this->generators['reverseNested'])
            ? $this->generators['reverseNested']
            : $this->generators['singleValueConstructor'];

        return $generator($aggName);
    }

    /**
     * @param $generator
     */
    public function setReverseNestedGenerator($generator)
    {
        $this->generators['reverseNested'] = $generator;
    }

    /**
     * Filter Aggregation
     */

    /**
     * @param $aggName
     * @param string $filterKey
     * @return mixed
     */
    public function getFilterGenerator($aggName, $filterKey = '')
    {
        $generator = isset($this->generators['filter'])
            ? $this->generators['filter']
            : $this->generators['singleValueConstructor'];

        return $generator($aggName, $filterKey);
    }

    /**
     * @param $generator
     */
    public function setFilterGenerator($generator)
    {
        $this->generators['filter'] = $generator;
    }

    /**
     * Filters Aggregation
     */

    /**
     * @param $aggName
     * @return mixed
     */
    public function getFiltersGenerator($aggName)
    {
        $generator = isset($this->generators['filters'])
            ? $this->generators['filters']
            : $this->generators['keyedBucketConstructor'];

        return $generator($aggName);
    }

    /**
     * @param $generator
     */
    public function setFiltersGenerator($generator)
    {
        $this->generators['filters'] = $generator;
    }

    /**
     * Terms Aggregation
     */

    /**
     * @param $aggName
     * @return mixed
     */
    public function getTermsGenerator($aggName)
    {
        $generator = isset($this->generators['terms'])
            ? $this->generators['terms']
            : $this->generators['bucketConstructor'];

        return $generator($aggName);
    }

    /**
     * @param $generator
     */
    public function setTermsGenerator($generator)
    {
        $this->generators['terms'] = $generator;
    }

    /**
     * Histogram Aggregation
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-bucket-histogram-aggregation.html
     */

    /**
     * Date Histogram Aggregation
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations-bucket-datehistogram-aggregation.html
     */

    /**
     * @param $aggName
     * @param bool $keyAsString
     * @return mixed
     */
    public function getDateHistogramGenerator($aggName, $keyAsString = true)
    {
        $generator = isset($this->generators['dateHistogram'])
            ? $this->generators['dateHistogram']
            : $this->generators['bucketConstructor'];

        return $generator($aggName, $keyAsString);
    }

    /**
     * @param $generator
     */
    public function setDateHistogramGenerator($generator)
    {
        $this->generators['dateHistogram'] = $generator;
    }
}