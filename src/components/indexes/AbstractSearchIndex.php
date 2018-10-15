<?php
namespace mirocow\elasticsearch\components\indexes;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\ElasticsearchException;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use mirocow\elasticsearch\components\queries\helpers\QueryHelper;
use mirocow\elasticsearch\components\queries\QueryBuilder;
use mirocow\elasticsearch\contracts\IndexInterface;
use mirocow\elasticsearch\contracts\QueryInterface;
use mirocow\elasticsearch\exceptions\SearchIndexerException;
use yii\helpers\ArrayHelper;

abstract class AbstractSearchIndex implements IndexInterface, QueryInterface
{
    public $hosts = [
      'localhost:9200'
    ];

    /** @var Client */
    private $client;

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
        $exists = true;
        try {
            $this->getClient()->indices()->get(
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
                throw new SearchIndexerException('Index ' . $this->name() . ' not found');
            }
            return;
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
     * Return result data from the store
     * @param string $arrayPath
     * @return array
     * @
     */
    public function result($arrayPath = '')
    {
        if(empty($this->result['hits']['hits'])){
            if(empty($this->result['aggregations'])) {
                return [];
            }
        }

        if($arrayPath) {
            return ArrayHelper::getValue($this->result, $arrayPath);
        }

        return $this->result;
    }

    /**
     * Execute query DSL
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl.html
     * @param array $query
     * @param string $method
     * @return $this
     * @throws \Exception
     */
    public function execute($query = [], $method = 'search')
    {
        $method = strtolower($method);

        if($query instanceof QueryBuilder){
            /** @var QueryBuilder $query */
            $query = $query->generateQuery();
        }

        if($method <> 'bulk') {
            $query = [
                'index' => $this->name(),
                // @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-search-type.html
                'type' => $this->type(),
                // @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-body.html
                'body' => $query,
            ];
        }

        try {
            $result = $this->getClient()->{$method}($query);
        } catch (\Exception $e) {
            throw $e;
        }

        $this->result = $result;

        return $this;
    }

    /**
     * Execute query DSL
     * @see https://ru.wikipedia.org/wiki/Okapi_BM25 for calculate _score
     * @param $query
     * @return AbstractSearchIndex
     * @throws \Exception
     */
    public function search($query)
    {
        return $this->execute($query, 'search');
    }

    /**
     * @param $query
     * @return AbstractSearchIndex
     * @throws \Exception
     */
    public function explain($query)
    {
        return $this->execute($query, 'explain');
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = ClientBuilder::create()
                ->setHosts($this->hosts)
                ->build();
        }

        return $this->client;
    }
}