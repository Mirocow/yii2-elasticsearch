<?php
namespace mirocow\elasticsearch;

use mirocow\elasticsearch\contracts\ProgressLogger;
use mirocow\elasticsearch\contracts\Indexer;
use mirocow\elasticsearch\components\factories\IndexerFactory;
use mirocow\elasticsearch\components\loggers\ConsoleProgressLogger;
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
            Indexer::class => [[IndexerFactory::class, 'getInstance'], []]
        ]);
    }
}