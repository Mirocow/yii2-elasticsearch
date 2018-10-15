<?php
namespace mirocow\elasticsearch\components\factories;

use mirocow\elasticsearch\contracts\IndexInterface;
use mirocow\elasticsearch\contracts\IndexerInterface;
use mirocow\elasticsearch\contracts\ProgressLoggerInterface;
use mirocow\elasticsearch\components\indexers\SearchIndexer;
use mirocow\elasticsearch\Module;
use Yii;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

class IndexerFactory
{
    /**
     * @return IndexerInterface
     */
    public static function getInstance() :IndexerInterface
    {
        /** @var ProgressLoggerInterface $logger */
        $logger = Yii::$container->get(ProgressLoggerInterface::class);

        $searchIndexer = new SearchIndexer($logger);
        foreach (self::getIndexes() as $indexConfig) {
            $className = $indexConfig['class'];

            if(!$className){
                throw new Exception("Search index class not found");
            }
            unset($indexConfig['class']);

            /** @var IndexInterface $index */
            $index = self::createIndex($className, $indexConfig);
            $searchIndexer->registerIndex($index);
        }

        return $searchIndexer;
    }

    /**
     * @param array $indexConfig
     * @return IndexInterface
     * @throws Exception
     */
    public static function createIndex($className, $indexConfig = [])
    {
        $configs = self::getIndexes();

        if($indexConfig) {
            $configs = ArrayHelper::merge($configs, $indexConfig);
            $indexConfig = [];
        }

        foreach ($configs as $config) {
            if($className == $config['class']){
                unset($config['class']);
                $indexConfig = ArrayHelper::merge($indexConfig, $config);
                break;
            }
            // Get config data from parrent class
            if(get_parent_class($className) == $config['class']){
                unset($config['class']);
                $indexConfig = ArrayHelper::merge($indexConfig, $config);
                break;
            }
        }

        if(!$indexConfig){
            throw new Exception("Config data not found");
        }

        return Yii::$container->get($className, $construct = [], $indexConfig);
    }

    /**
     * @return null|\mirocow\elasticsearch\Module
     */
    public static function getModule()
    {
        return Yii::$app->getModule(Module::MODULE_NAME);
    }

    /**
     * @return array|string[]
     */
    public static function getIndexes()
    {
        $module = self::getModule();

        return $module->indexes ?? [];
    }
}