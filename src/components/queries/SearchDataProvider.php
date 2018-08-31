<?php
namespace mirocow\elasticsearch\components\queries;

use mirocow\elasticsearch\components\indexes\AbstractSearchIndex;
use mirocow\elasticsearch\components\indexes\ModelPopulate;
use mirocow\elasticsearch\components\queries\Aggregation\Aggregation;
use mirocow\elasticsearch\components\queries\Aggregation\AggregationMulti;
use mirocow\elasticsearch\contracts\Index;
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
     * @var array
     */
    private $response = [];

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

        if (!$this->search instanceof Index) {
            throw new InvalidConfigException('The "search" property must be an instance of a class that implements the \mirocow\elasticsearch\contracts\Index e.g. mirocow\elasticsearch\components\indexes\AbstractSearchIndex or its subclasses.');
        }

        if (($sort = $this->getSort()) !== false) {
            $this->query->sort($sort);
        }

        if (($pagination = $this->getPagination()) !== false) {
            $pagination->totalCount = $this->getTotalCount();
            if ($pagination->totalCount === 0) {
                return [];
            }
            $this->query->limit($pagination->getLimit())
                ->offset($pagination->getOffset());
        }

        $this->response = $this->search
            ->search($this->query)
            ->result();

        if(!$this->response){
            return [];
        }

        if (!$this->modelClass instanceof ModelPopulate) {
            throw new InvalidConfigException('The "modelClass" property must be an instance of a class that implements the \mirocow\elasticsearch\contracts\Populate e.g. mirocow\elasticsearch\components\indexes\ModelPopulate or its subclasses.');
        }

        return $this->modelClass->setResult($this->response)->all();
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

        $query = $query
            ->aggregations(null)
            ->limit(1);

        $response = $this->search
            ->search($query)
            ->result();

        if(empty($response['hits']['total'])){
            return 0;
        }

        return $response['hits']['total'];
    }

    protected function prepareAggregations()
    {
        $this->prepare();

        if(!empty($this->response['aggregations'])) {
            /** @var Aggregation|AggregationMulti $aggs */
            if($aggs = $this->query->aggs) {
                $this->setAggregations($aggs->generateResults($this->response['aggregations']));
            }
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
        return $this->sort;
    }
}
