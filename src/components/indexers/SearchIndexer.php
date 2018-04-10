<?php
namespace mirocow\elasticsearch\components\indexers;

use mirocow\elasticsearch\contracts\ProgressLogger;
use mirocow\elasticsearch\contracts\Index;
use mirocow\elasticsearch\contracts\Indexer;
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
    public function __construct(
      ProgressLogger $progressLogger
    ) {
        $this->progressLogger = $progressLogger;
    }

    /** @inheritdoc */
    public function registerIndex(Index $index)
    {
        if (!isset($this->indexes[$index->name()])) {
            $this->indexes[$index->name()] = $index;
        }

    }

    public function getIndex(string $indexName)
    {
        foreach ($this->indexes as $index) {
            if ($indexName instanceof $index) {
                $this->progressLogger->logMessage('Load index: ' . $index->name());
                return $index;
            }
        }
        throw new SearchIndexerException('Index ' . $indexName . ' is not registered in search indexer');
    }

    /** @inheritdoc */
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

    /** @inheritdoc */
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
     * @inheritdoc
     * @throws SearchIndexerException
     */
    public function rebuild(string $indexName)
    {
        $this->destroyIndex($indexName);
        $this->createIndex($indexName);
        foreach ($this->indexes as $index) {
            $this->populate($index->name());
        }
    }

    /** @inheritdoc */
    public function populate(string $indexName)
    {
        if (!isset($this->indexes[$indexName])) {
            throw new SearchIndexerException('Index ' . $indexName . ' is not registered in search indexer');
        }

        $index = $this->indexes[$indexName];
        if (!$index->exists()) {
            throw new SearchIndexerException('Index ' . $indexName . ' is not initialized');
        }

        $this->progressLogger->logMessage('Indexing documents for index: ' . $index->name());

        $totalSteps = $index->documentCount();
        $step       = 1;
        foreach ($index->documentIds() as $documentId) {
            $index->addById($documentId);
            $this->progressLogger->logProgress($totalSteps, $step);
            $step++;
        }
    }

    /** @inheritdoc */
    public function createIndex(string $indexName)
    {
        foreach ($this->indexes as $index) {
            if ($index->name() === $indexName) {
                $this->progressLogger->logMessage('Creating index: ' . $index->name());
                $index->create();
                return;
            }
        }
        throw new SearchIndexerException('Index ' . $indexName . ' is not registered in search indexer');
    }

    /** @inheritdoc */
    public function destroyIndex(string $indexName)
    {
        foreach ($this->indexes as $index) {
            if ($index->name() === $indexName) {
                $this->progressLogger->logMessage('Destroying index: ' . $index->name());
                $index->destroy();
                return;
            }
        }
        throw new SearchIndexerException('Index ' . $indexName . ' is not registered in search indexer');
    }

    /**
     * @inheritdoc
     * @throws SearchIndexerException
     */
    public function createIndexes()
    {
        foreach ($this->indexes as $index) {
            if (!$index->exists()) {
                $this->createIndex($index->name());
            }
        }
    }

    /**
     * @inheritdoc
     * @throws SearchIndexerException
     */
    public function destroyIndexes()
    {
        foreach ($this->indexes as $index) {
            if ($index->exists()) {
                $this->destroyIndex($index->name());
            }
        }
    }

    /** @inheritdoc */
    public function upgradeIndex(string $indexName)
    {
        foreach ($this->indexes as $index) {
            if ($index->name() === $indexName) {
                $this->progressLogger->logMessage('Upgrade index: ' . $index->name());
                $index->upgrade();
                return;
            }
        }
        throw new SearchIndexerException('Index ' . $indexName . ' is not registered in search indexer');
    }

    /**
     * @inheritdoc
     * @throws SearchIndexerException
     */
    public function upgradeIndexes()
    {
        foreach ($this->indexes as $index) {
            if ($index->exists()) {
                $this->upgradeIndex($index->name());
            }
        }

    }

}