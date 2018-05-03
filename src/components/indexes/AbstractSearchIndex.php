<?php
namespace mirocow\elasticsearch\components\indexes;

use mirocow\elasticsearch\components\queries\helpers\QueryHelper;
use mirocow\elasticsearch\contracts\Index;
use mirocow\elasticsearch\exceptions\SearchIndexerException;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\ElasticsearchException;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

abstract class AbstractSearchIndex implements Index
{
    public $hosts = [
      'localhost:9200'
    ];

    public $withSource = false;

    /**
     * @var array
     */
    public $body = [];

    /** @var Client */
    protected $client;

    /**
     * @var array
     */
    private $query = [];

    /**
     * @var array
     */
    private $filter = [];

    /**
     * @var array
     */
    private $aggs = [];

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
    private $size = 10;

    /**
     * @var array
     */
    private $sort = [];

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

    /**
     * AbstractSearchIndex constructor.
     */
    public function __construct()
    {
        $this->client = ClientBuilder::create()
                                     ->setHosts($this->hosts)
                                     ->build();
    }

    /** @inheritdoc */
    public function exists() :bool
    {
        $exists = true;
        try {
            $this->client->indices()->get(
              [
                'index' => $this->name()
              ]
            );
        } catch (Missing404Exception $e) {
            $exists = false;
        }
        return $exists;
    }

    /** @inheritdoc */
    public function create()
    {
        if ($this->exists()) {
            throw new SearchIndexerException('Index '.$this->name(). ' already exists');
        }
        try {
            $settings = $this->indexConfig();
            $this->client->indices()->create($settings);
        } catch (ElasticsearchException $e) {
            throw new SearchIndexerException('Error creating '.$this->name(). ' index', $e->getCode(), $e);
        }
    }

    /** @inheritdoc */
    public function upgrade()
    {
        if (!$this->exists()) {
            throw new SearchIndexerException('Index '.$this->name(). ' not found');
        }
        try {
            $settings = $this->indexConfig();
            if(empty($settings['body']['mappings'][$this->name()])){
                throw new SearchIndexerException("Error remaping index ".$this->name());
            }
            $mapping = [
              'index' => $this->name(),
              'type' => $this->type(),
              'body' => $settings['body']['mappings'][$this->name()],
            ];
            $this->client->indices()->putMapping($mapping);
        } catch (ElasticsearchException $e) {
            throw new SearchIndexerException('Error upgrading '.$this->name(). ' index', $e->getCode(), $e);
        }
    }

    /**
     * @throws SearchIndexerException
     */
    public function destroy()
    {
        if (!$this->exists()) {
            throw new SearchIndexerException('Index '.$this->name(). ' does not exist');
        }
        $this->client->indices()->delete(
          [
            'index' => $this->name()
          ]
        );
    }

    /**
     * @param int $documentId
     * @return void
     */
    protected function deleteInternal(int $documentId)
    {
        $this->client->delete(
          [
            'index' => $this->name(),
            'type' => $this->type(),
            'id' => $documentId
          ]
        );
    }

    /**
     * @return array
     */
    abstract protected function indexConfig() :array;

    /**
     * @param int $documentId
     * @return array
     */
    public function getDocument(int $documentId)
    {
        $query = [
          'index' => $this->name(),
          'type' => $this->type(),
          'id' => $documentId
        ];

        return $this->client->get($query);
    }

    /**
     * @return array
     */
    public function body()
    {
        return $this->body;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return Json::encode($this->body);
    }

    /**
     * @param string|array $query
     * @return $this
     */
    public function query($query = '')
    {
        $this->query = QueryHelper::query($query);

        return $this;
    }

    /**
     * @param int $size
     * @param int $from
     * @return $this
     */
    public function limit(int $size = 0, int $from = 0)
    {
        $this->from = $from;
        $this->size = $size;

        return $this;
    }

    /**
     * @param array $fieldsName
     * @return $this
     */
    public function sort(array $fieldsName = [])
    {
        if($fieldsName) {
            $this->sort = $fieldsName;
        }

        return $this;
    }

    /**
     * @param array $aggregations
     * @return $this
     */
    public function aggregations(array $aggregations = [])
    {
        $this->aggs = $aggregations;

        return $this;
    }

    /**
     * @param array $highlight
     * @return $this
     */
    public function highlight(array $highlight = [])
    {
        $this->highlight = $highlight;

        return $this;
    }

    /**
     * @param array $source
     * @return $this
     */
    public function source(array $source = [])
    {
        $this->_source = $source;

        return $this;
    }

    /**
     * @param array $filter
     * @return $this
     */
    public function filter(array $filter = [])
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Return result data from the store
     * @param string $arrayPath
     * @return array
     * @
     */
    public function result($arrayPath = '')
    {
        if($arrayPath) {
            return ArrayHelper::getValue($this->result, $arrayPath);
        }

        return $this->result;
    }

    /**
     * Save result data into the store
     * @param bool $store
     * @return $this
     */
    public function store(bool $store = false)
    {
        $this->store = $store;

        return $this;
    }

    /**
     * Release store
     */
    public function release()
    {
        $this->store = [];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function search($flush = true)
    {
        if($flush) {
            $this->body = [];
        }

        $fields = [
            'query', // @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-match-query.html
            'filter', // @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-post-filter.html
            'from', // @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-from-size.html
            'size', // @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-from-size.html
            'aggs', // @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-aggregations.html
            'highlight', // @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-highlighting.html
            'sort', // @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-sort.html
            '_source', // @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-source-filtering.html
            'stored_fields', // TODO: @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-stored-fields.html
            'script_fields', // TODO: @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-script-fields.html
            'docvalue_fields', // TODO: @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-docvalue-fields.html
            'rescore', // TODO: @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-rescore.html
            'explain', // TODO: @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-explain.html
            'min_score', // TODO: @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-min-score.html
            'collapse', // TODO: @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-collapse.html

        ];

        foreach ($fields as $param){
            if(!empty($this->{$param})){
                $this->body[$param] = $this->{$param};
            }
        }

        if(!$this->_source) {
            $this->body['_source'] = $this->withSource;
        }

        /**
         * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/mapping-id-field.html
         * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-sort.html
         */
        if(!$this->sort) {
            $this->body['sort'] = QueryHelper::sortBy(['_id' => ['order' => 'asc']]);
        }

        $query = [
          'index' => $this->name(),
          'type' => $this->type(), // @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-search-type.html
          'body' => $this->body,
        ];

        try {
            $result =  $this->client->search($query);
        } catch (\Exception $e){
            throw $e;
        }

        if($this->store){
            $this->result = $result;
        }

        return $result;
    }
}