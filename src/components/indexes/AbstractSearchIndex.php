<?php
namespace mirocow\elasticsearch\components\indexes;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Helper\Iterators\SearchHitIterator;
use Elasticsearch\Helper\Iterators\SearchResponseIterator;
use mirocow\elasticsearch\components\queries\QueryBuilder;
use mirocow\elasticsearch\contracts\IndexInterface;
use mirocow\elasticsearch\contracts\QueryInterface;
use mirocow\elasticsearch\exceptions\SearchClientException;
use mirocow\elasticsearch\exceptions\SearchIndexerException;
use mirocow\elasticsearch\exceptions\SearchQueryException;
use Yii;
use yii\base\Exception;
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

    /**
     * @return bool
     * @throws SearchClientException
     */
    public function exists() :bool
    {
        return $this->getClient()->indices()->exists([
            'index' => $this->name()
        ]);
    }

    /**
     * @param bool $skipExists
     *
     * @throws SearchClientException
     * @throws SearchIndexerException
     */
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

    /**
     * @param bool $skipNotExists
     *
     * @return array|void
     * @throws SearchClientException
     * @throws SearchIndexerException
     */
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
            return $this->getClient()->indices()->putMapping($mapping);
        } catch (ElasticsearchException $e) {
            throw new SearchIndexerException('Error upgrading '.$this->name(). ' index', $e->getCode(), $e);
        }
    }

    /**
     * @param bool $skipNotExists
     *
     * @return array|void
     * @throws SearchClientException
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
        try {
            return $this->getClient()->indices()->delete([
                'index' => $this->name()
            ]);
        } catch (ElasticsearchException $e) {
            throw new SearchIndexerException('Error deleting '.$this->name(). ' index', $e->getCode(), $e);
        }
    }

    /**
     * @return array
     */
    abstract protected function indexConfig() :array;

    /**
     * @deprecated
     * @param int $documentId
     * @param $document
     * @return array
     */
    public function index(int $documentId, $document)
    {
        $this->documentCreate($documentId, $document);
    }

    /**
     * @param int $documentId
     * @param $document
     * @return array
     */
    public function documentCreate(int $documentId, $document)
    {
        $query = [
            'id' => $documentId,
            'body' => $document,
        ];

        return $this->execute($query, 'index');
    }

    /**
     * @deprecated
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/docs-get.html
     * @param int $documentId
     * @return array
     */
    public function getById(int $documentId, $onlySource = true)
    {
        return $this->documentGetById($documentId, $onlySource);
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/docs-get.html
     * @param int $documentId
     * @param bool $onlySource
     * @return array
     */
    public function documentGetById(int $documentId, $onlySource = true){
        $query = [
            'id' => $documentId,
        ];

        $client = $this->getClient();

        if($onlySource){
            return $this->execute($query, 'getSource');
        }

        return $this->execute($query, 'get');
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-exists-query.html
     * @param int $documentId
     * @return array
     */
    public function documentExists(int $documentId)
    {
        $query = [
            'id' => $documentId,
        ];

        return $this->execute($query, 'exists');
    }

    /**
     * @deprecated
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/docs-delete.html
     * @param int $documentId
     * @return array
     * @throws SearchClientException
     */
    public function removeById(int $documentId)
    {
        return $this->documentRemoveById($documentId);
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/docs-delete.html
     * @param int $documentId
     * @return array
     * @throws SearchClientException
     */
    public function documentRemoveById(int $documentId)
    {
        $query = [
            'id' => $documentId,
        ];

        return $this->execute($query, 'delete');
    }

    /**
     * @deprecated
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/docs-update.html
     * @param int $documentId
     * @param $document
     * @param string $type doc, script
     * @return array
     */
    public function updateById(int $documentId, $document, $type = 'doc')
    {
        return $this->documentUpdateById($documentId, $document, $type);
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/docs-update.html
     * @param int $documentId
     * @param $document
     * @param string $type doc, script
     * @return array
     */
    public function documentUpdateById(int $documentId, $document, $type = 'doc')
    {
        $query = [
            'id' => $documentId,
            'body' => [
                $type => $document,
            ],
        ];

        return $this->execute($query, 'update');
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
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-scroll.html
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
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/docs-bulk.html
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
     * @param array $query
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-search-type.html
     *
     * @return array
     */
    protected function prepareQuery($query)
    {
        // Query as QueryBuilder
        if ($query instanceof QueryBuilder) {
            $query = [
                'body' => $query->generateQuery(),
            ];
        }

        // Query as stdClass created by QueryHelper
        elseif($query instanceof \stdClass){
            $query = [
                'body' => $query,
            ];
        }

        // Query as array
        if(is_array($query)) {
            $query = ArrayHelper::merge($query, [
                'index' => $this->name(),
                'type' => $this->type(),
            ]);
        }

        if(!$query){
            throw new SearchClientException('Query not found');
        }

        return $query;
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
        try {
            $query = $this->prepareQuery($query);
            if ($params) {
                $query = ArrayHelper::merge($query, $params);
            }

            $requestBody = '';
            if (YII_DEBUG) {
                $profile = '/' . $this->name() . '/' . $this->type() . '/_' . $method;
                Yii::beginProfile($profile, __METHOD__);
                $requestBody = json_encode($query);
                Yii::info($requestBody, __METHOD__);
            }

            $client = $this->getClient();
            switch ($method) {
                case 'bulk':
                    $this->result = $client->bulk($query);
                break;
                case 'search':
                case 'explain':
                case 'scroll':
                    if ($method == 'scroll') {
                        $this->result = new SearchResponseIterator($client, $query);
                    } else {
                        $this->result = $client->{$method}($query);
                    }
                break;
                default:
                    if(method_exists($client, $method)) {
                        return $client->{$method}($query);
                    } else {
                        throw new SearchClientException("Unknown client method");
                    }
            }

            if (YII_DEBUG) {
                if (!($query instanceof SearchResponseIterator)) {
                    Yii::info($this->result, __METHOD__);
                }
                Yii::endProfile($profile, __METHOD__);
            }
        } catch (BadRequest400Exception | Missing404Exception $e){
            throw new SearchQueryException($requestBody, $e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e);
        } catch (SearchClientException $e){
            throw $e;
        }

        return $this;
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
}