<?php
namespace mirocow\elasticsearch\controllers\index\actions;

use mirocow\elasticsearch\contracts\Indexer;
use mirocow\elasticsearch\exceptions\SearchIndexerException;
use mirocow\elasticsearch\console\ConsoleAction;
use yii\console\Controller;

class ActionPopulate extends ConsoleAction
{
    public $skipNotExists = false;

    /** @var Indexer */
    private $indexer;

    /**
     * ActionPopulate constructor.
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
     * Add documents into index/indexes
     * @param string $indexName
     */
    public function run(string $indexName = '')
    {
        try {
            $this->indexer->populate($indexName, $this->skipNotExists);
        } catch (SearchIndexerException $e) {
            $this->stdErr($e->getMessage());
            if($previous = $e->getPrevious()) {
                $this->stdDebug($previous->getMessage());
            }
        }
    }
}
