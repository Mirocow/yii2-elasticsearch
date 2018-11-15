<?php
namespace mirocow\elasticsearch\components\indexes;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\ElasticsearchException;
use Elasticsearch\Helper\Iterators\SearchHitIterator;
use Elasticsearch\Helper\Iterators\SearchResponseIterator;
use mirocow\elasticsearch\components\queries\QueryBuilder;
use mirocow\elasticsearch\contracts\IndexInterface;
use mirocow\elasticsearch\contracts\QueryInterface;
use mirocow\elasticsearch\exceptions\SearchClientException;
use mirocow\elasticsearch\exceptions\SearchIndexerException;
use Yii;
use yii\helpers\ArrayHelper;

abstract class AbstractSearchIndex implements IndexInterface, QueryInterface
{
    public $hosts = [
      'localhost:9200'
    ];

    /** @var string */
    public $index_name = 'index_name';

    /** @var string */
    public $index_type = 'index_type';

    /** @var Client */
    private $client;

    /** @var array|SearchResponseIterator|mixed */
    private $result;

    /**
     * AbstractSearchIndex constructor.
     */
    public function __construct()
    {

    }

    /** @inheritdoc */
    public function name()
    {
        return $this->index_name;
    }

    /** @inheritdoc */
    public function type()
    {
        return $this->index_type;
    }

    /** @inheritdoc */
    public function exists() :bool
    {
        return $this->getClient()->indices()->exists([
            'index' => $this->name()
        ]);
    }

    /** @inheritdoc */
    public function create($skipExists = false)
    {
        if ($this->exists()) {
            if(!$skipExists) {
                throw new SearchIndexerException('Index ' . $this->name() . ' already exists');
            }
            return;
        }
        try {
            $settings = $this->indexConfig();
            $this->getClient()->indices()->create($settings);
        } catch (ElasticsearchException $e) {
            throw new SearchIndexerException('Error creating '.$this->name(). ' index', $e->getCode(), $e);
        }
    }

    /** @inheritdoc */
    public function upgrade($skipNotExists = false)
    {
        if (!$this->exists()) {
            if(!$skipNotExists) {
                throw new SearchIndexerException('Index ' . $this->name() . ' does not exist');
            }
            return;
        }
        try {
            $settings = $this->indexConfig();
            if(empty($settings['body']['mappings'][$this->type()])){
                throw new SearchIndexerException("Error remaping type ".$this->type());
            }
            $mapping = [
                'index' => $this->name(),
                'type' => $this->type(),
                'body' => $settings['body']['mappings'][$this->type()],
            ];
            $this->getClient()->indices()->putMapping($mapping);
        } catch (ElasticsearchException $e) {
            throw new SearchIndexerException('Error upgrading '.$this->name(). ' index', $e->getCode(), $e);
        }
    }

    /**
     * @throws SearchIndexerException
     */
    public function destroy($skipNotExists = false)
    {
        if (!$this->exists()) {
            if(!$skipNotExists) {
                throw new SearchIndexerException('Index ' . $this->name() . ' does not exist');
            }
            return;
        }
        $this->getClient()->indices()->delete(
          [
            'index' => $this->name()
          ]
        );
    }

    /**
     * @return array
     */
    abstract protected function indexConfig() :array;

    /**
     * @deprecated
     * @param int $documentId
     * @param $document
     * @param int $parent
     * @return array
     */
    public function index(int $documentId, $document, $parent = null)
    {
        $this->documentCreate($documentId, $document);
    }

    /**
     * @param int $documentId
     * @param $document
     * @param int $parent
     * @return array
     */
    public function documentCreate(int $documentId, $document, $parent = null)
    {
        $query = [
            'index' => $this->name(),
            'type' => $this->type(),
            'id' => $documentId,
            'body' => $document,
        ];

        return $this->getClient()->index($query);
    }

    /**
     * @deprecated
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/docs-get.html
     * @param int $documentId
     * @param int $parent
     * @return array
     */
    public function getById(int $documentId, $onlySource = true, $parent = null)
    {
        return $this->documentGetById($documentId, $onlySource);
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/docs-get.html
     * @param int $documentId
     * @param bool $onlySource
     * @param int $parent
     * @return array
     */
    public function documentGetById(int $documentId, $onlySource = true, $parent = null){
        $query = [
            'index' => $this->name(),
            'type' => $this->type(),
            'id' => $documentId
        ];

        $client = $this->getClient();

        if($onlySource){
            return $client->getSource($query);
        }

        return $client->get($query);
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-exists-query.html
     * @param int $documentId
     * @param $document
     * @param string $type doc, script
     * @return array
     */
    public function documentExists(int $documentId)
    {
        $query = [
            'index' => $this->name(),
            'type' => $this->type(),
            'id' => $documentId,
        ];

        return $this->getClient()->exists($query);
    }

    /**
     * @deprecated
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/docs-delete.html
     * @param int $documentId
     * @param int $parent
     * @return void
     */
    public function removeById(int $documentId, $parent = null)
    {
        $this->documentRemoveById($documentId);
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/docs-delete.html
     * @param int $documentId
     * @param int $parent
     * @return void
     */
    public function documentRemoveById(int $documentId, $parent = null)
    {
        $query = [
            'index' => $this->name(),
            'type' => $this->type(),
            'id' => $documentId
        ];

        $this->getClient()->delete($query);
    }

    /**
     * @deprecated
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/docs-update.html
     * @param int $documentId
     * @param $document
     * @param string $type doc, script
     * @param int $parent
     * @return array
     */
    public function updateById(int $documentId, $document, $type = 'doc', $parent = null)
    {
        return $this->documentUpdateById($documentId, $document, $type);
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/docs-update.html
     * @param int $documentId
     * @param $document
     * @param string $type doc, script
     * @param int $parent
     * @return array
     */
    public function documentUpdateById(int $documentId, $document, $type = 'doc', $parent = null)
    {
        $query = [
            'index' => $this->name(),
            'type' => $this->type(),
            'id' => $documentId,
            'body' => [
                $type => $document
            ],
        ];

        return $this->getClient()->update($query);
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/docs-update-by-query.html
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/docs-update.html
     */
    public function documentUpdateByQuery()
    {
        throw new SearchClientException("Not implemented yet");
    }

    /**
     * Return result data from the store
     *
     * @return array|SearchHitIterator|mixed
     */
    public function result()
    {
        if($this->result instanceof SearchResponseIterator){
            return new SearchHitIterator($this->result);
        } else {
            if(empty($this->result['hits']['hits'])){
                if(empty($this->result['aggregations'])) {
                    return [];
                }
            }
            return $this->result;
        }
    }

    /**
     * Execute query DSL
     * @param array $query
     * @param string $method
     * @param array $params
     *
     * @return $this
     * @throws \Exception
     */
    public function execute($query = [], $method = 'search', $params = [])
    {
        if ($query instanceof QueryBuilder) {
            /** @var QueryBuilder $query */
            $query = $query->generateQuery();
        }

        $method = strtolower($method);

        if ($method <> 'bulk') {
            $query = [
                'index' => $this->name(),
                // @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-search-type.html
                'type' => $this->type(),
                // @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-body.html
                'body' => $query,
            ];
        }

        if ($params) {
            $query = ArrayHelper::merge($query, $params);
        }

        $profile = 'GET /' . $this->name() . '/' . $this->type() . '/_' . $method;

        if (YII_DEBUG) {
            $requestBody = json_encode($query);
            Yii::info($requestBody, __METHOD__);
            Yii::beginProfile($profile, __METHOD__);
        }

        try {
            switch ($method) {
                case 'scroll':
                    $this->result = new SearchResponseIterator($this->getClient(), $query);
                break;
                case 'bulk':
                    $this->result = $this->getClient()->bulk($query);
                break;
                case 'search':
                case 'explain':
                    $this->result = $this->getClient()->{$method}($query);
                break;
                default:
                    throw new SearchClientException("Unknown client method");
            }
        } catch (\Exception $e) {
            throw $e;
        }

        if (YII_DEBUG) {
            if (!($query instanceof SearchResponseIterator)) {
                Yii::info($this->result, __METHOD__);
            }
            Yii::endProfile($profile, __METHOD__);
        }

        return $this;
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search.html
     * @see https://ru.wikipedia.org/wiki/Okapi_BM25 for calculate _score
     * @param $query
     * @param array $params
     * @return AbstractSearchIndex
     * @throws \Exception
     */
    public function search($query, $params = [])
    {
        return $this->execute($query, 'search', $params);
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-scroll.html
     * @param $query
     * @param array $params
     * @return AbstractSearchIndex
     * @throws \Exception
     */
    public function scroll($query, $params = ['scroll' => '5s', 'body' => ['size' => 50]])
    {
        return $this->execute($query, 'scroll', $params);
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/6.5/docs-bulk.html
     * @param $query
     * @param array $params
     * @return AbstractSearchIndex
     * @throws \Exception
     */
    public function bulk($query, $params = [])
    {
        return $this->execute($query, 'bulk', $params);
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-explain.html
     * @param $query
     * @param array $params
     * @return AbstractSearchIndex
     * @throws \Exception
     */
    public function explain($query, $params = [])
    {
        return $this->execute($query, 'explain', $params);
    }

    /**
     * @return Client
     * @throws SearchClientException
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = ClientBuilder::create()
                ->setHosts($this->hosts)
                ->build();
            if(!$this->client->ping()){
                throw new SearchClientException("Elasticsearch server doesn't answer");
            }
        }

        return $this->client;
    }
}