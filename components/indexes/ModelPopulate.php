<?php
namespace common\modules\elasticsearch\components\indexes;

use yii\db\ActiveQuery;
use yii\db\ActiveQueryTrait;
use yii\db\ActiveRecord;
use yii\db\ActiveRecordInterface;

class ModelPopulate
{

    use ActiveQueryTrait;

    public $indexBy = 'id';

    private $refresh = true;

    protected $result = [];

    public function __construct(array &$result = [])
    {
        $this->result = $result;
    }

    public function refresh($refresh = false)
    {
        $this->refresh = $refresh;

        return $this;
    }

    /**
     * Converts the raw query results into the format as specified by this query.
     * This method is internally used to convert the data fetched from database
     * into the format as required by this query.
     * @return array the converted query result
     * @since 2.0.4
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
                } else {
                    $key = call_user_func($this->indexBy, $row);
                }
                $models[$key] = $row;
            }
        } else {
            /* @var $class ActiveRecord */
            $class = $this->modelClass;

            if ($this->indexBy === null) {
                foreach ($rows as $row) {
                    $model = $class::instantiate($row);
                    /** @var ActiveRecord $modelClass */
                    $modelClass = get_class($model);
                    if(isset($row['_source'])) {
                        $modelClass::populateRecord($model, $row['_source']);
                    } else {
                        $model = $modelClass::findOne($row['_id']);
                    }
                    if($this->refresh){
                        $model->refresh();
                    }
                    $models[] = $model;
                }
            } else {
                foreach ($rows as $row) {
                    $model = $class::instantiate($row);
                    /** @var ActiveRecord $modelClass */
                    $modelClass = get_class($model);
                    if(isset($row['_source'])) {
                        $modelClass::populateRecord($model, $row['_source']);
                    } else {
                        $model = $modelClass::findOne($row['_id']);
                    }
                    if (is_string($this->indexBy)) {
                        $key = $model->{$this->indexBy};
                    } else {
                        $key = call_user_func($this->indexBy, $model);
                    }
                    if($this->refresh){
                        $model->refresh();
                    }
                    $models[$key] = $model;
                }
            }
        }

        return $models;
    }

    /**
     * Finds records corresponding to one or multiple relations and populates them into the primary models.
     * @param array $with a list of relations that this query should be performed with. Please
     * refer to [[with()]] for details about specifying this parameter.
     * @param array|ActiveRecord[] $models the primary models (can be either AR instances or arrays)
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
     * Executes the query and returns all results as an array.
     * If this parameter is not given, the `elasticsearch` application component will be used.
     * @return array the query results. If the query results in nothing, an empty array will be returned.
     */
    public function all()
    {
        $this->search();

        if (empty($this->result['hits']['hits'])) {
            return [];
        }
        $this->result = $this->result['hits']['hits'];

        return $this->populate();
    }

    /**
     * Executes the query and returns all results as an array.
     * If this parameter is not given, the `elasticsearch` application component will be used.
     * @return array the query results. If the query results in nothing, an empty array will be returned.
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
     * @inheritdoc
     */
    public function search()
    {
        $result = &$this->result;

        if (!empty($result['hits']['hits']) && !$this->asArray) {
            $models = $this->createModels($result['hits']['hits']);
            if (!empty($this->with)) {
                $this->findWith($this->with, $models);
            }
            foreach ($models as $model) {
                $model->afterFind();
            }
            $result['hits']['hits'] = $models;
        }

        return $result;
    }

}