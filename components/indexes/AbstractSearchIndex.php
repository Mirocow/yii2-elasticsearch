<?php
namespace common\modules\elasticsearch\components\indexes;

use common\modules\elasticsearch\components\queries\helpers\QueryHelper;
use common\modules\elasticsearch\contracts\Index;
use common\modules\elasticsearch\exceptions\SearchIndexerException;
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
    private $body = [];

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
     * @var int
     */
    private $from = 0;

    /**
     * @var int
     */
    private $size = 10;

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
                'index' => static::name()
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
            throw new SearchIndexerException('Index '.static::name(). ' already exists');
        }
        try {
            $settings = static::indexConfig();
            $this->client->indices()->create($settings);
        } catch (ElasticsearchException $e) {
            throw new SearchIndexerException('Error creating '.static::name(). ' index', $e->getCode(), $e);
        }
    }

    /** @inheritdoc */
    public function upgrade()
    {
        if (!$this->exists()) {
            throw new SearchIndexerException('Index '.static::name(). ' not found');
        }
        try {
            $settings = static::indexConfig();
            if(empty($settings['body']['mappings'][static::name()])){
                throw new SearchIndexerException("Error remaping index ".static::name());
            }
            $mapping = [
              'index' => static::name(),
              'type' => static::type(),
              'body' => $settings['body']['mappings'][static::name()],
            ];
            $this->client->indices()->putMapping($mapping);
        } catch (ElasticsearchException $e) {
            throw new SearchIndexerException('Error upgrading '.static::name(). ' index', $e->getCode(), $e);
        }
    }

    /**
     * @throws SearchIndexerException
     */
    public function destroy()
    {
        if (!$this->exists()) {
            throw new SearchIndexerException('Index '.static::name(). ' does not exist');
        }
        $this->client->indices()->delete(
          [
            'index' => static::name()
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
            'index' => static::name(),
            'type' => static::type(),
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
          'index' => static::name(),
          'type' => static::type(),
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
    public function searchDocuments()
    {
        $fields = [
            'query', // Query
            'filter', // Filter execute after request
            'from', // Limit
            'size', // Limit
            'aggs', // Group
            'sort', // Sort
        ];

        $this->body = [];

        foreach ($fields as $param){
            if(!empty($this->{$param})){
                $this->body[$param] = $this->{$param};
            }
        }

        $this->body['_source'] = $this->withSource;

        $query = [
          'index' => static::name(),
          'type' => static::type(),
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