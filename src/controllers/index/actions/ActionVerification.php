<?php
namespace mirocow\elasticsearch\controllers\index\actions;

use mirocow\elasticsearch\contracts\IndexerInterface;
use mirocow\elasticsearch\exceptions\SearchIndexerException;
use mirocow\elasticsearch\console\ConsoleAction;
use yii\console\Controller;

class ActionVerification extends ConsoleAction
{
    public $skipNotExists = false;

    /** @var IndexerInterface */
    private $indexer;

    /**
     * ActionPopulate constructor.
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
        parent::__construct($id, $controller, $config);
        $this->indexer = $indexer;
    }

    /**
     * Add documents into index/indexes
     * @param string $indexName
     */
    public function run(string $indexName = '')
    {
        try {
            if(!$this->indexer->verification($indexName)){
                $this->indexer->rebuild($indexName, $this->skipExists, $this->skipNotExists);
            }
        } catch (SearchIndexerException $e) {
            $this->stdErr($e->getMessage());
            if($previous = $e->getPrevious()) {
                $this->stdDebug($previous->getMessage());
            }
        }
    }
}
