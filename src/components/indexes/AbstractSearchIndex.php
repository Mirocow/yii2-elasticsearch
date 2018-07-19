<?php
namespace mirocow\elasticsearch\components\indexes;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\ElasticsearchException;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use mirocow\elasticsearch\contracts\Index;
use mirocow\elasticsearch\exceptions\SearchIndexerException;
use yii\helpers\ArrayHelper;

abstract class AbstractSearchIndex implements Index
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
     * @param int $documentId
     * @param $document
     */
    public function index(int $documentId, $document)
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
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/docs-get.html
     * @param int $documentId
     * @return array
     */
    public function getById(int $documentId)
    {
        $query = [
          'index' => $this->name(),
          'type' => $this->type(),
          'id' => $documentId
        ];

        return $this->getClient()->get($query);
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/docs-delete.html
     * @param int $documentId
     * @return void
     */
    public function removeById(int $documentId)
    {
        $query = [
            'index' => $this->name(),
            'type' => $this->type(),
            'id' => $documentId
        ];

        $this->getClient()->delete($query);
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/docs-update.html
     * @param int $documentId
     * @param $document
     * @param string $type doc, script
     */
    public function updateById(int $documentId, $document, $type = 'doc')
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
            return [];
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
     * @return $this
     * @throws \Exception
     */
    public function search($query = [])
    {
        $query = [
            'index' => $this->name(),
            // @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-search-type.html
            'type'  => $this->type(),
            // @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-body.html
            'body'  => $query,
        ];

        try {
            $result = $this->getClient()->search($query);
        } catch (\Exception $e) {
            throw $e;
        }

        $this->result = $result;

        return $this;
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