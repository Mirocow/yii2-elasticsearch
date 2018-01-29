<?php
namespace common\modules\elasticsearch\components\queries\Aggregation;

class AggregationMulti implements AggregationInterface
{
    /** @var AggregationInterface[] */
    protected $aggs;

    /**
     * AggregationMulti constructor.
     * @param AggregationInterface[] $aggs
     */
    public function __construct($aggs)
    {
        $this->aggs = $aggs;
    }

    public function setAgg($key, AggregationInterface $agg)
    {
        $this->aggs[$key] = $agg;
    }

    /**
     * Generate the query to be submitted to Elasticsearch
     *
     * @return array
     */
    public function generateQuery()
    {
        $out = [];
        $i = 0;
        foreach ($this->aggs as $label => $agg) {
            $queries = $agg->generateQuery();
            foreach ($queries as $key => $query) {
                $out["{$i}_$key"] = $query; // prepending the key with a number and underscore to prevent key collision and help with parsing results
            }
            $i++;
        }

        return $out;
    }

    /**
     * Return an array representing the parsed results
     *
     * @param $results
     * @return array
     */
    public function generateResults($results)
    {
        $out = ['Total' => 0];
        unset($results['doc_count']);

        // preparing the result sets by parsing out the prefix
        // if there is a multi under this multi, it will all be on the same level so
        // the separate prefixes have to be parsed out, and each prefix set has to be passed into generate results as one
        $resultSets = [];
        foreach ($results as $resultKey => $result) {
            $prefix = substr($resultKey, 0, strpos($resultKey, '_') + 1);
            $resultKey = Aggregation::removePrefix($resultKey, $prefix);
            $resultSets[$prefix][$resultKey] = $result;
        }
        unset($results);

        $i = 0;
        foreach ($this->aggs as $label => $agg) {
            $prefix = $i++ . "_";
            $out['aggs'][$label] = $agg->generateResults($resultSets[$prefix]);
            if (is_numeric($out['aggs'][$label])) {
                $out['Total'] += $out['aggs'][$label];
            } elseif (isset($out['aggs'][$label]['Total'])) {
                $out['Total'] += $out['aggs'][$label]['Total'];
            }
        }

        return $out;
    }
}
