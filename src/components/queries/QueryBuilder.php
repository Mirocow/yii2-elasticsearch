<?php
namespace mirocow\elasticsearch\components\queries;

use mirocow\elasticsearch\components\queries\Aggregation\Aggregation;
use mirocow\elasticsearch\components\queries\Aggregation\AggregationMulti;
use mirocow\elasticsearch\components\queries\helpers\QueryHelper;
use mirocow\elasticsearch\exceptions\SearchQueryException;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

class QueryBuilder
{
    /**
     * @var null
     */
    private $query = null;

    /**
     * @var array
     */
    private $body = [];

    /**
     * Can accept parameters:
     * '*', false - e.t.c.
     * @var array|string|bool
     */
    private $withSource = false;

    /**
     * @var array
     */
    private $filter = [];

    /**
     * @var array
     */
    private $post_filter = [];

    /**
     * @var array
     */
    private $script_fields = [];

    /**
     * @var array
     */
    private $docvalue_fields = [];

    /**
     * @var Aggregation|AggregationMulti
     */
    public $aggs = [];

    /**
     * @var array
     */
    private $highlight = [];

    /**
     * @var array
     */
    private $_source = [];

    /**
     * @var int
     */
    private $from = 0;

    /**
     * @var int
     */
    private $size = 10000;

    /**
     * @var array
     */
    private $sort = [];

    /**
     * @var array
     */
    private $rescore = [];

    /**
     * @var bool
     */
    private $release = true;

    /**
     * @var bool
     */
    private $store = false;

    /**
     * @var array
     */
    private $result = [];

    /** @var float */
    private $min_score = 0.5;

    /**
     * @param string $key
     * @param $value
     * @return $this
     */
    public function set($key, $value)
    {
        if($value) {
            ArrayHelper::setValue($this->query, $key, $value);
        }
        return $this;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return ArrayHelper::getValue($this->query, $key);
    }

    /**
     * @param $value
     * @return $this
     */
    public function add($value)
    {
        if($value) {
            $this->query = ArrayHelper::merge($this->query, $value);
        }
        return $this;
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-match-query.html
     * @param string|array $query
     * @return $this
     */
    public function query($query = '')
    {
        if($query) {
            $query = QueryHelper::query($query);
            $this->query = $query->query;
        }
        return $this;
    }

    /**
     * The size parameter allows you to configure the maximum amount of hits to be returned.
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-from-size.html
     * @param int $size
     * @return $this
     */
    public function limit(int $size = 10000)
    {
        $this->size = $size;
        return $this;
    }

    /**
     * Pagination of results can be done by using the from and size parameters.
     * The from parameter defines the offset from the first result you want to fetch.
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-from-size.html
     * @param int $from
     * @return $this
     */
    public function offset(int $from = 0)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * Allows to add one or more sort on specific fields. Each sort can be reversed as well.
     * The sort is defined on a per field level, with special field name for _score to sort by score, and _doc to sort by index order.
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-sort.html
     * @param array|null $fieldsName
     * @return $this
     */
    public function sort($fieldsName = [])
    {
        $this->sort = $fieldsName;
        return $this;
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations.html
     * @param Aggregation|AggregationMulti|null $aggregations
     * @return $this
     */
    public function aggregations($aggregations)
    {
        $this->aggs = $aggregations;
        return $this;
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-highlighting.html
     * @param array|null $highlight
     * @return $this
     */
    public function highlight(array $highlight = [])
    {
        $this->highlight = $highlight;
        return $this;
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-source-filtering.html
     * @param array $source
     * @return $this
     */
    public function source(array $source = [])
    {
        $this->_source = $source;
        return $this;
    }

    /**
     * The query rescorer executes a second query only on the Top-K results returned by the query and post_filter phases.
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-rescore.html
     * @param array $rescore
     * @return $this
     */
    public function rescore(array $rescore = [])
    {
        $this->rescore = $rescore;
        return $this;
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-post-filter.html
     * @param array $filter
     * @return $this
     */
    public function filter(array $filter = [])
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-post-filter.html
     * @param array $filter
     * @return $this
     */
    public function post_filter(array $filter = [])
    {
        $this->post_filter = $filter;
        return $this;
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-script-fields.html
     * @param array $fields
     * @return $this
     */
    public function script_fields(array $fields = [])
    {
        $this->script_fields = $fields;
        return $this;
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-docvalue-fields.html
     * @param array $fields
     * @return $this
     */
    public function docvalue_fields(array $fields = [])
    {
        $this->docvalue_fields = $fields;
        return $this;
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-min-score.html
     * @param float $min
     * @return $this
     */
    public function min_score($min = 0.5)
    {
        $this->min_score = $min;
        return $this;
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-source-filtering.html
     * @param array|string|bool $data
     * @return $this
     */
    public function withSource($data = '*')
    {
        $this->withSource = $data;
        return $this;
    }

    /**
     * @return string|array
     */
    protected function prepareQuery()
    {
        return $this->query;
    }

    /**
     * @return string|array
     */
    protected function prepareFilter()
    {
        return $this->filter;
    }

    /**
     * @return string|array
     */
    protected function prepareFrom()
    {
        return $this->from;
    }

    /**
     * @return string|array
     */
    protected function prepareSize()
    {
        return $this->size;
    }

    /**
     * @return string|array
     */
    protected function preparePostfilter()
    {
        return $this->post_filter;
    }

    /**
     * @return string|array
     */
    protected function prepareScriptfields()
    {
        return $this->script_fields;
    }

    /**
     * @return string|array
     */
    protected function prepareDocvaluefields()
    {
        return $this->docvalue_fields;
    }

    /**
     * @return string|array
     */
    protected function prepareMinscore()
    {
        return $this->min_score;
    }

    /**
     * @return array|null
     */
    protected function prepareAggs()
    {
        if(is_array($this->aggs) && $this->aggs){
            throw new SearchQueryException('must be an instance of a class e.g. \mirocow\elasticsearch\components\queries\Aggregation\Aggregation or \mirocow\elasticsearch\components\queries\Aggregation\AggregationMulti or its subclasses.');
        }

        if($this->aggs instanceof Aggregation){
            $aggregations = $this->aggs->generateQuery();
        }

        if($this->aggs instanceof AggregationMulti){
            $aggregations = $this->aggs->generateQuery();
        }

        if(!isset($aggregations)){
            return [];
        }

        return $aggregations;
    }

    /**
     * @return string|array
     */
    protected function prepareHighlight()
    {
        return $this->highlight;
    }

    /**
     * @return string|array
     */
    protected function prepareSort()
    {
        return $this->sort;
    }

    /**
     * @return string|array
     */
    protected function prepareSource()
    {
        return $this->_source;
    }

    /**
     * @return string|array
     */
    protected function prepareRescore()
    {
        return $this->rescore;
    }

    /**
     * @return array Elasticsearch DSL body
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-body.html
     */
    public function generateQuery()
    {

        $fields = [

            'query',
            'filter',
            'from',
            'size',
            'aggs',
            'highlight',
            'sort',
            'post_filter',
            'source',
            'rescore',
            //'stored_fields', // TODO: @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-stored-fields.html
            'script_fields',
            'docvalue_fields',
            'min_score',
            //'collapse', // TODO: @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-collapse.html

        ];

        foreach ($fields as $field) {
            if($partQuery = call_user_func_array([$this, 'prepare' . ucwords(str_replace('_', '', $field))], [])) {
                $this->body[$field] = $partQuery;
            }
        }

        /**
         * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/mapping-source-field.html
         */
        if (!$this->_source) {
            $this->body[ '_source' ] = $this->withSource;
        }

        /**
         * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/mapping-id-field.html
         * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-sort.html
         */
        if (!$this->sort && !is_null($this->sort)) {
            $this->body[ 'sort' ] = QueryHelper::sortBy(['_score' => SORT_DESC]);
        }

        return $this->body;
    }

    /**
     * @return false|string
     */
    public function __toString()
    {
        return json_encode($this->generateQuery());
    }

}
