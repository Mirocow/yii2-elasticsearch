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
    protected $client;

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
            $result = $this->client->search($query);
        } catch (\Exception $e) {
            throw $e;
        }

        $this->result = $result;

        return $this;
    }
}