<?php
namespace mirocow\elasticsearch\controllers\index\actions;

use mirocow\elasticsearch\contracts\IndexerInterface;
use mirocow\elasticsearch\exceptions\SearchIndexerException;
use mirocow\elasticsearch\console\ConsoleAction;
use yii\console\Controller;

class ActionUpgrade extends ConsoleAction
{
    public $skipNotExists = false;

    /** @var IndexerInterface */
    private $indexer;

    /**
     * ActionRebuild constructor.
     * @param string $id
     * @param Controller $controller
     * @param IndexerInterface $indexer
     * @param array $config
     */
    public function __construct(
        $id,
        Controller $controller,
        IndexerInterface $indexer,
        array $config = []
    ) {
        $this->indexer = $indexer;
        parent::__construct($id, $controller, $config);
    }

    /**
     * Update documents
     * @param string $indexName
     */
    public function run(string $indexName = '')
    {
        try {
            $this->indexer->upgradeIndexes($indexName, $this->skipNotExists);
        } catch (SearchIndexerException $e) {
            $this->stdErr($e->getMessage());
            if($previous = $e->getPrevious()) {
                $this->stdDebug($previous->getMessage());
            }
        }
    }
}
