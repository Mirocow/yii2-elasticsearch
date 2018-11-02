<?php
namespace mirocow\elasticsearch\components\indexers;

use mirocow\elasticsearch\contracts\IndexInterface;
use mirocow\elasticsearch\contracts\IndexerInterface;
use mirocow\elasticsearch\contracts\ProgressLoggerInterface;
use mirocow\elasticsearch\exceptions\SearchIndexerException;
use yii\base\Exception;

final class SearchIndexer implements IndexerInterface
{
    /** @var IndexInterface[] */
    private $indexes = [];

    /** @var ProgressLoggerInterface */
    private $progressLogger;

    /**
     * SearchIndexer constructor.
     * @param ProgressLoggerInterface $progressLogger
     */
    public function __construct(ProgressLoggerInterface $progressLogger)
    {
        $this->progressLogger = $progressLogger;
    }

    /**
     * @param IndexInterface $index
     */
    public function registerIndex(IndexInterface $index)
    {
        if (!isset($this->indexes[$index->name()])) {
            $this->indexes[$index->name()] = $index;
        }

    }

    /**
     * @param $indexName
     * @return array|IndexInterface[]
     * @throws SearchIndexerException
     */
    private function getIndexes($indexName)
    {
        $indexes = [];

        if(!$indexName){
            $indexes = $this->indexes;
        } else {
            foreach ($this->indexes as $index) {
                if ($index->name() === $indexName) {
                    $indexes[] = $index;
                    break;
                }
            }
        }

        if(!$indexes){
            if($indexName) {
                throw new SearchIndexerException('Index ' . $indexName . ' is not registered in search indexer');
            } else {
                throw new SearchIndexerException('Indexes can not be empty');
            }
        }

        return $indexes;
    }

    /**
     * @param string $indexName
     * @return array|IndexInterface[]|mixed
     * @throws SearchIndexerException
     */
    public function getIndex(string $indexName = '')
    {
        return $this->getIndexes($indexName);
    }

    /**
     * @param string $indexName
     * @param bool $skipExists
     * @throws SearchIndexerException
     */
    public function createIndex(string $indexName = '', $skipExists = false)
    {
        foreach ($this->getIndexes($indexName) as $index) {
            $this->progressLogger->logMessage('Creating index: ' . $index->name() . ' type: ' . $index->type());
            $index->create($skipExists);
        }

    }

    /**
     * @param string $indexName
     * @throws SearchIndexerException
     */
    public function destroyIndex(string $indexName = '', $skipNotExists = false)
    {
        foreach ($this->getIndexes($indexName) as $index) {
            $this->progressLogger->logMessage('Destroying index: ' . $index->name() . ' type: ' . $index->type());
            $index->destroy($skipNotExists);
        }
    }

    /**
     * @param string $indexName
     * @throws SearchIndexerException
     */
    public function upgradeIndex(string $indexName = '', $skipNotExists = false)
    {
        foreach ($this->getIndexes($indexName) as $index) {
            $this->progressLogger->logMessage('Upgrade index: ' . $index->name() . ' type: ' . $index->type());
            $index->upgrade($skipNotExists);
        }
    }

    /**
     * @param mixed $document
     * @throws SearchIndexerException
     */
    public function index($document)
    {
        foreach ($this->indexes as $index) {
            if (!$index->accepts($document)) {
                continue;
            }

            if (!$index->exists()) {
                throw new SearchIndexerException('Index ' . $index->name() . ' is not initialized');
            }

            $index->add($document);
            return;
        }
        throw new SearchIndexerException('No index registered for provided document');
    }

    /**
     * @param mixed $document
     * @throws SearchIndexerException
     */
    public function remove($document)
    {
        foreach ($this->indexes as $index) {
            if ($index->accepts($document)) {
                $index->remove($document);
                return;
            }
        }
        throw new SearchIndexerException('No index registered for provided document');
    }

    /**
     * @param string $indexName
     * @throws Exception
     * @throws SearchIndexerException
     */
    public function populate(string $indexName = '', $skipNotExists = false)
    {
        foreach ($this->getIndexes($indexName) as $index) {

            if (!$index->exists()) {
                if($skipNotExists) {
                    throw new SearchIndexerException('Index ' . $indexName . ' is not initialized');
                }

                return;
            }

            $this->progressLogger->logMessage('Indexing documents for index: ' . $index->name() . ' type: ' . $index->type());

            $totalSteps = $index->documentCount();
            $step = 1;
            foreach ($index->documentIds() as $document) {
                if(is_array($document) && isset($document['id'])){
                    $documentId = $document['id'];
                } elseif(is_numeric($document)) {
                    $documentId = $document;
                } else {
                    throw new Exception('Wrong format index');
                }
                $index->addById($documentId);
                $this->progressLogger->logProgress($totalSteps, $step);
                $step++;
            }

        }
    }

    /**
     * @param string $indexName
     * @throws SearchIndexerException
     */
    public function rebuild(string $indexName = '', $skipExists = false, $skipNotExists = false)
    {
        $this->destroyIndex($indexName, $skipNotExists);
        $this->createIndex($indexName, $skipExists);
        $this->populate($indexName, $skipNotExists);
    }

    /**
     * @param string $indexName
     *
     * @return bool
     */
    public function verification(string $indexName = '')
    {
        return true;
    }

}