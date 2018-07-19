<?php
namespace mirocow\elasticsearch\components\factories;

use mirocow\elasticsearch\contracts\Index;
use mirocow\elasticsearch\contracts\Indexer;
use mirocow\elasticsearch\contracts\ProgressLogger;
use mirocow\elasticsearch\components\indexers\SearchIndexer;
use mirocow\elasticsearch\Module;
use Yii;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

class IndexerFactory
{
    /**
     * @return Indexer
     */
    public static function getInstance() :Indexer
    {
        /** @var ProgressLogger $logger */
        $logger = Yii::$container->get(ProgressLogger::class);

        $module = Yii::$app->getModule(Module::MODULE_NAME);

        /** @var array[] $indexes */
        $indexes = $module->indexes ?? [];

        $searchIndexer = new SearchIndexer($logger);
        foreach ($indexes as $indexConfig) {
            $className = $indexConfig['class'];

            if(!$className){
                throw new Exception("Search index class not found");
            }
            unset($indexConfig['class']);

            /** @var Index $index */
            $index = self::createIndex($className, $indexConfig);
            $searchIndexer->registerIndex($index);
        }

        return $searchIndexer;
    }

    /**
     * @param array $indexConfig
     * @return Index
     * @throws Exception
     */
    public static function createIndex($className , $indexConfig = [])
    {
        $module = Yii::$app->getModule(Module::MODULE_NAME);

        /** @var array[] $indexes */
        $indexes = $module->indexes ?? [];

        foreach ($indexes as $config) {
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
}