<?php
namespace common\modules\elasticsearch;

use common\modules\elasticsearch\contracts\ProgressLogger;
use common\modules\elasticsearch\contracts\Indexer;
use common\modules\elasticsearch\components\factories\IndexerFactory;
use common\modules\elasticsearch\components\loggers\ConsoleProgressLogger;
use Yii;
use yii\base\BootstrapInterface;

class Bootstrap implements BootstrapInterface
{
    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        Yii::$container->setSingletons([
            ProgressLogger::class => ConsoleProgressLogger::class,
            Indexer::class => [[IndexerFactory::class, 'create'], []]
        ]);
    }
}