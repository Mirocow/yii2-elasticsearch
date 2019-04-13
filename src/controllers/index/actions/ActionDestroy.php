<?php
namespace mirocow\elasticsearch\controllers\index\actions;

use mirocow\elasticsearch\contracts\IndexerInterface;
use mirocow\elasticsearch\exceptions\SearchIndexerException;
use mirocow\elasticsearch\console\ConsoleAction;
use yii\console\Controller;

class ActionDestroy extends ConsoleAction
{
    public $skipNotExists = false;

    /** @var IndexerInterface */
    private $indexer;

    /**
     * ActionCreate constructor.
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
     * Destroy index/indexes
     * @param string $indexName
     */
    public function run(string $indexName = '')
    {
        try {
            $this->indexer->destroyIndex($indexName, $this->skipNotExists);
        } catch (SearchIndexerException $e) {
            $this->stdErr($e->getMessage());
            $this->stdDebug($e);
        }
    }
}
