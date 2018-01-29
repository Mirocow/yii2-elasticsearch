<?php
namespace common\modules\elasticsearch\components\factories;
use common\modules\elasticsearch\contracts\Index;
use common\modules\elasticsearch\contracts\Indexer;
use common\modules\elasticsearch\contracts\ProgressLogger;
use common\modules\elasticsearch\components\indexers\SearchIndexer;
use common\modules\elasticsearch\Module;
use Yii;
class IndexerFactory
{
    /**
     * @return Indexer
     */
    public static function create() :Indexer
    {
        /** @var ProgressLogger $logger */
        $logger = Yii::$container->get(ProgressLogger::class);

        /** @var array[] $indexes */
        $indexes = Yii::$app->getModule(Module::MODULE_NAME)->indexes ?? [];

        $searchIndexer = new SearchIndexer($logger);
        foreach ($indexes as $indexConfig) {
            /** @var Index $index */
            $index = Yii::$container->get($indexConfig['class']);
            $searchIndexer->registerIndex($index);
        }

        return $searchIndexer;
    }
}