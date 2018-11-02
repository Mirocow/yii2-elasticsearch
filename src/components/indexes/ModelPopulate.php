<?php
namespace mirocow\elasticsearch\components\indexes;

use mirocow\elasticsearch\contracts\PopulateInterface;
use mirocow\elasticsearch\exceptions\SearchQueryException;
use yii\db\ActiveQuery;
use yii\db\ActiveQueryTrait;
use yii\db\ActiveRecord;
use yii\db\ActiveRecordInterface;
use yii\helpers\ArrayHelper;

class ModelPopulate implements PopulateInterface
{

    use ActiveQueryTrait;

    public $indexBy = 'id';

    private $select = '';

    protected $result = [];

    public function __construct(array &$result = [])
    {
        $this->setResult($result);
    }

    /**
     * @inheritdoc
     */
    public function setResult(array &$result = [])
    {
        $this->result = $result;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function populate()
    {
        $rows = &$this->result;

        if ($this->indexBy === null) {
            return $rows;
        }
        $models = [];
        $i = 0;
        foreach ($rows as $key => $row) {
            if ($this->indexBy !== null) {
                if (is_string($this->indexBy)) {
                    if (isset($row['fields'][$this->indexBy])) {
                        $key = reset($row['fields'][$this->indexBy]);
                    } elseif (isset($row['_source'][$this->indexBy])){
                        $key = $row['_source'][$this->indexBy];
                    } elseif (isset($row[$this->indexBy])){
                        $key = $row[$this->indexBy];
                    } else {
                        $key = $i++;
                    }
                } else {
                    $key = call_user_func($this->indexBy, $row);
                }
            }

            if(!empty($row['_source'])){
                $row = $row['_source'];
            }

            $models[$key] = $row;
        }
        return $models;
    }

    /**
     * Converts found rows into model instances
     * @param array $rows
     * @return array|ActiveRecord[]
     * @since 2.0.4
     */
    protected function createModels($rows)
    {
        $models = [];
        if ($this->asArray) {
            if ($this->indexBy === null) {
                return $rows;
            }
            foreach ($rows as $row) {
                if (is_string($this->indexBy)) {
                    $key = '_id';
                    if (isset($row['fields'][$this->indexBy])) {
                        $key = reset($row['fields'][$this->indexBy]);
                    } elseif (isset($row['_source'][$this->indexBy])){
                        $key = $row['_source'][$this->indexBy];
                    } elseif (isset($row[$key])){
                        $key = $row[$key];
                    }
                } elseif ($this->indexBy instanceof \Closure) {
                    $key = call_user_func($this->indexBy, $row);
                }

                if (empty($key)) {
                    $models[] = $row;
                } else {
                    $models[ $key ] = $row;
                }
            }
        } else {
            /* @var $class ActiveRecord */
            $class = $this->modelClass;

            if($this->indexBy === null){
                $this->indexBy = '_id';
            }

            foreach ($rows as $row) {
                $model = $class::instantiate($row);

                /** @var ActiveRecord $modelClass */
                $modelClass = get_class($model);

                // Reserved elasticsearch sorce field
                if(isset($row['_source'])) {
                    $row = $row['_source'];
                }

                // Reseved Elasticsearch ID
                if(is_numeric($row)){
                    $row = ['_id' => $row];
                }

                // Try fill model attributes from _source
                $modelClass::populateRecord($model, $row);

                // Model is corrupted and we haven`t all model`s attributes
                if(count($model->attributes) <> count($row)) {

                    // If exists special elasticserch field
                    if(!empty($row['_id'])){
                        $id = $row['_id'];
                    } elseif(!empty($row[$this->indexBy])) {
                        $id = $row[$this->indexBy];
                    }

                    // Skip if model id not found
                    if(empty($id)){
                        continue;
                    }

                    $model = $modelClass::findOne(['id' => $id]);

                    // Skip if model not found
                    if(!$model){
                        continue;
                    }

                }

                // Sort models by indexBy
                if (is_string($this->indexBy)) {
                    $key = $model->{$this->indexBy};
                } elseif ($this->indexBy instanceof \Closure) {
                    $key = call_user_func($this->indexBy, $model);
                }

                if (empty($key)) {
                    $models[] = $model;
                } else {
                    $models[ $key ] = $model;
                }
            }
        }

        return $models;
    }

    /**
     * @inheritdoc
     */
    public function findWith($with, &$models)
    {
        $primaryModel = reset($models);
        if (!$primaryModel instanceof ActiveRecordInterface) {
            $primaryModel = new $this->modelClass();
        }
        $relations = $this->normalizeRelations($primaryModel, $with);
        /* @var $relation ActiveQuery */
        foreach ($relations as $name => $relation) {
            if ($relation->asArray === null) {
                // inherit asArray from primary query
                $relation->asArray($this->asArray);
            }
            $relation->populateRelation($name, $models);
        }
    }

    /**
     * @inheritdoc
     */
    public function all()
    {
        $this->search();

        if(!$this->result){
            return [];
        }

        return $this->populate();
    }

    /**
     * @inheritdoc
     */
    public function one()
    {
        $return = $this->all();

        if(!$return){
            return [];
        }

        return end($return);
    }

    /**
     * @param $select
     */
    public function select($select)
    {
        $this->select = $select;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function search()
    {
        $result = &$this->result;

        // Check result`s format
        if(isset($result['hits']['hits'])) {
            $items = $result['hits']['hits'];
        } elseif(is_array($result)) {
            $items = array_values($result);
        }

        if(!empty($items)) {

            // Select fields from result
            if ($this->select) {
                $hits = [];
                foreach ($items as $item) {
                    // Include *, name.* as wildcard
                    if(substr($this->select, -2) == '.*') {
                        $key = substr($this->select, 0, strlen($this->select)-2);
                        $values = ArrayHelper::getValue($item, $key);
                        if($values) {
                            $hits = array_merge($hits, $values);
                        }
                    } else {
                        if($this->select == '*'){
                            $value = $item;
                        } else {
                            $value = ArrayHelper::getValue($item, $this->select);
                        }
                         if($value) {
                            $hits[] = $value;
                            unset($value);
                        }
                    }
                }

                if(!$hits){
                    throw new SearchQueryException(\Yii::t('app', 'Result data by select "{select}" not found', ['select' => $this->select ]));
                }

                $items = $hits;
                unset($hits);
            }

            // Load ActiveRecord`s models
            if (!$this->asArray) {
                $models = $this->createModels($items);
                if (!empty($this->with)) {
                    $this->findWith($this->with, $models);
                }
                foreach ($models as $model) {
                    $model->afterFind();
                }
                $items = $models;
                unset($models);
            }

        }

        if(!empty($items)){
            $this->result = $items;
            unset($items);
        }

        return $this->result;
    }

}
