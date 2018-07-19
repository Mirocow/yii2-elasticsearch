<?php
namespace mirocow\elasticsearch\controllers\index\actions;

use mirocow\elasticsearch\contracts\Indexer;
use mirocow\elasticsearch\exceptions\SearchIndexerException;
use mirocow\elasticsearch\console\ConsoleAction;
use yii\console\Controller;

class ActionCreate extends ConsoleAction
{
    public $skipExists = false;

    /** @var Indexer */
    private $indexer;

    /**
     * ActionCreate constructor.
     * @param string $id
     * @param Controller $controller
     * @param Indexer $indexer
     * @param array $config
     */
    public function __construct(
        $id,
        Controller $controller,
        Indexer $indexer,
        array $config = []
    ) {
        parent::__construct($id, $controller, $config);
        $this->indexer = $indexer;
    }

    /**
     * Create index/indexes
     * @param string $indexName
     */
    public function run(string $indexName = '')
    {
        try {
            $this->indexer->createIndex($indexName, $this->skipExists);
        } catch (SearchIndexerException $e) {
            $this->stdErr($e->getMessage());
            if($previous = $e->getPrevious()) {
                $this->stdDebug($previous->getMessage());
            }
        }
    }
}
