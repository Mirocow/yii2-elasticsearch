<?php
namespace mirocow\elasticsearch;

use mirocow\elasticsearch\contracts\ProgressLoggerInterface;
use mirocow\elasticsearch\contracts\IndexerInterface;
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
            ProgressLoggerInterface::class => ConsoleProgressLogger::class,
            IndexerInterface::class => [[IndexerFactory::class, 'getInstance'], []]
        ]);
    }
}