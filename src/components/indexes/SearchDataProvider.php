<?php
namespace mirocow\elasticsearch\components\indexes;

use mirocow\elasticsearch\components\indexes\AbstractSearchIndex;
use mirocow\elasticsearch\components\indexes\ModelPopulate;
use mirocow\elasticsearch\components\queries\Aggregation\Aggregation;
use mirocow\elasticsearch\components\queries\Aggregation\AggregationMulti;
use mirocow\elasticsearch\components\queries\helpers\QueryHelper;
use mirocow\elasticsearch\components\queries\QueryBuilder;
use mirocow\elasticsearch\contracts\IndexInterface;
use yii\base\InvalidConfigException;
use yii\data\BaseDataProvider;
use yii\helpers\ArrayHelper;

class SearchDataProvider extends BaseDataProvider
{
    /**
     * @var QueryBuilder
     */
    public $query;

    /**
     * @var AbstractSearchIndex
     */
    public $search;

    /**
     * @var ModelPopulate
     */
    public $modelClass;

    /** @var array */

    /**
     * @var array|null
     */
    private $aggregations = [];

    /**
     * @var array
     */
    private $sort = [];

    /**
     * {@inheritdoc}
     */
    protected function prepareModels()
    {

        if (!$this->query instanceof QueryBuilder) {
            throw new InvalidConfigException('The "query" property must be an instance of a class \mirocow\elasticsearch\components\queries\QueryBuilder or its subclasses.');
        }

        if (!$this->search instanceof IndexInterface) {
            throw new InvalidConfigException('The "search" property must be an instance of a class that implements the \mirocow\elasticsearch\contracts\IndexInterface e.g. mirocow\elasticsearch\components\indexes\AbstractSearchIndex or its subclasses.');
        }

        if (!$this->modelClass instanceof ModelPopulate) {
            throw new InvalidConfigException('The "modelClass" property must be an instance of a class that implements the \mirocow\elasticsearch\contracts\PopulateInterface e.g. mirocow\elasticsearch\components\indexes\ModelPopulate or its subclasses.');
        }

        $query = clone $this->query;

        if (($pagination = $this->getPagination()) !== false) {
            $pagination->totalCount = $this->getTotalCount();
            if ($pagination->totalCount === 0) {
                return [];
            }
            $query->limit($pagination->getLimit())
                ->offset($pagination->getOffset());
        }

        if (($sort = $this->getSort()) !== false) {
            $query->sort($sort);
        }

        $response = $this->search
            ->search(
                $query
                    ->aggregations(null)
            )
            ->result();

        if(!$response){
            return [];
        }

        return $this->modelClass
            ->setResult($response)
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareKeys($models)
    {
        return array_keys($models);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTotalCount()
    {
        $query = clone $this->query;

        $response = $this->search
            ->search(
                $query
                    ->aggregations(null)
                    ->sort(null)
                    ->withSource(false)
                    ->limit(0)
            )
            ->result();

        if(!$response){
            return 0;
        }

        return $response['hits']['total'];
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function prepareAggregations()
    {
        $query = clone $this->query;

        $response = $this->search
            ->search(
                $query
                    ->withSource(false)
                    ->sort(null)
                    ->limit(0)
            )
            ->result();

        if(!$response){
            return [];
        }

        /** @var Aggregation|AggregationMulti $aggs */
        if(($aggs = $this->query->aggs) && !empty($response['aggregations'])) {
            $this->setAggregations($aggs->generateResults($response['aggregations']));
        }
    }

    /**
     * @param array $aggregations
     */
    public function setAggregations($aggregations = [])
    {
        if($aggregations) {
            $this->aggregations = ArrayHelper::merge($this->aggregations, $aggregations);
        }
    }

    /**
     * @return array
     */
    public function getAggregations()
    {
        $this->prepareAggregations();

        return $this->aggregations;
    }

    /**
     * @param array $value
     */
    public function setSort($value)
    {
        $this->sort = $value;
    }

    /**
     * @return array
     */
    public function getSort()
    {
        if(!$this->sort){
            $this->sort = QueryHelper::sortBy(['_score' => SORT_DESC]);
        }

        return $this->sort;
    }
}
