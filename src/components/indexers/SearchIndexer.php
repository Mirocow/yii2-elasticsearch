<?php
namespace mirocow\elasticsearch\components\indexers;

use mirocow\elasticsearch\contracts\Index;
use mirocow\elasticsearch\contracts\Indexer;
use mirocow\elasticsearch\contracts\ProgressLogger;
use mirocow\elasticsearch\exceptions\SearchIndexerException;

final class SearchIndexer implements Indexer
{
    /** @var Index[] */
    private $indexes = [];

    /** @var ProgressLogger */
    private $progressLogger;

    /**
     * SearchIndexer constructor.
     * @param ProgressLogger $progressLogger
     */
    public function __construct(ProgressLogger $progressLogger)
    {
        $this->progressLogger = $progressLogger;
    }

    /**
     * @param Index $index
     */
    public function registerIndex(Index $index)
    {
        if (!isset($this->indexes[$index->name()])) {
            $this->indexes[$index->name()] = $index;
        }

    }

    /**
     * @param $indexName
     * @return array|Index[]
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
     * @return array|Index[]|mixed
     * @throws SearchIndexerException
     */
    public function getIndex(string $indexName = '')
    {
        return $this->getIndexes($indexName);
    }

    /**
     * @param string $indexName
     * @throws SearchIndexerException
     */
    public function createIndex(string $indexName = '')
    {
        foreach ($this->getIndexes($indexName) as $index) {
            $this->progressLogger->logMessage('Creating index: ' . $index->name());
            $index->create();
        }

    }

    /**
     * @param string $indexName
     * @throws SearchIndexerException
     */
    public function destroyIndex(string $indexName = '')
    {
        foreach ($this->getIndexes($indexName) as $index) {
            $this->progressLogger->logMessage('Destroying index: ' . $index->name());
            $index->destroy();
        }
    }

    /**
     * @param string $indexName
     * @throws SearchIndexerException
     */
    public function upgradeIndex(string $indexName = '')
    {
        foreach ($this->getIndexes($indexName) as $index) {
            $this->progressLogger->logMessage('Upgrade index: ' . $index->name());
            $index->upgrade();
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
     * @throws SearchIndexerException
     */
    public function populate(string $indexName = '')
    {
        foreach ($this->getIndexes($indexName) as $index) {

            if (!$index->exists()) {
                throw new SearchIndexerException('Index ' . $indexName . ' is not initialized');
            }

            $this->progressLogger->logMessage('Indexing documents for index: ' . $index->name());

            $totalSteps = $index->documentCount();
            $step = 1;
            foreach ($index->documentIds() as $documentId) {
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
    public function rebuild(string $indexName = '')
    {
        $this->destroyIndex($indexName);
        $this->createIndex($indexName);
        $this->populate($indexName);
    }

}