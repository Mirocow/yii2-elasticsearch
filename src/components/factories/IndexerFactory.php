<?php
namespace mirocow\elasticsearch\components\factories;

use mirocow\elasticsearch\contracts\IndexInterface;
use mirocow\elasticsearch\contracts\IndexerInterface;
use mirocow\elasticsearch\contracts\ProgressLoggerInterface;
use mirocow\elasticsearch\components\indexers\SearchIndexer;
use mirocow\elasticsearch\exceptions\SearchQueryException;
use mirocow\elasticsearch\Module;
use Yii;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

class IndexerFactory
{
    /**
     * @return IndexerInterface
     * @throws SearchQueryException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public static function getInstance() :IndexerInterface
    {
        /** @var ProgressLoggerInterface $logger */
        $logger = Yii::$container->get(ProgressLoggerInterface::class);

        $searchIndexer = new SearchIndexer($logger);
        foreach (self::getIndexes() as $indexConfig) {
            if(isset($indexConfig['class'])) {
                /** @var IndexInterface $index */
                $index = self::createIndex($indexConfig['class'], $indexConfig);
                $searchIndexer->registerIndex($index);
            }
        }

        return $searchIndexer;
    }

    /**
     * @param $className
     * @param array $indexConfig
     *
     * @return object
     * @throws SearchQueryException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public static function createIndex($className, $indexConfig = [])
    {
        if(!class_exists($className)){
            throw new SearchQueryException("Index class not found");
        }

        $configs = self::getIndexes();

        if($indexConfig) {
            $configs = ArrayHelper::merge($configs, $indexConfig);
        }

        foreach ($configs as $config) {
            if(!class_exists($config['class'])){
                throw new SearchQueryException("Index class not found");
            }
            if($className == $config['class']){
                $indexConfig = ArrayHelper::merge($indexConfig, $config);
                break;
            } else {
                $obj = new \ReflectionClass($className);
                if ($obj->isSubclassOf($config['class'])) {
                    $indexConfig = ArrayHelper::merge($indexConfig, $config);
                    break;
                }
            }
        }

        if(!$indexConfig){
            throw new SearchQueryException("Config data not found");
        }

        return Yii::$container->get($className, $construct = [], $indexConfig);
    }

    /**
     * @return \yii\base\Module|null
     */
    public static function getModule()
    {
        return Yii::$app->getModule(Module::MODULE_NAME);
    }

    /**
     * @return array|mixed
     */
    public static function getIndexes()
    {
        $module = self::getModule();

        return $module->indexes ?? [];
    }
}
