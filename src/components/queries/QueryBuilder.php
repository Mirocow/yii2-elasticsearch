<?php
namespace mirocow\elasticsearch\components\queries;

use yii\helpers\ArrayHelper;

class QueryBuilder
{
    protected $query = null;

    private function init()
    {
        if(!$this->query){
            $this->query = new Query;
        }
    }

    public function set($key, $value)
    {
        $this->init();
        ArrayHelper::setValue($this->query, $key, $value);
    }

    public function get($key)
    {
        $this->init();
        ArrayHelper::getValue($this->query, $key);
    }

    public function add($value)
    {
        $this->init();
        $this->query = ArrayHelper::merge($this->query, $value);
    }

    public function generateQuery()
    {
        return $this->query;
    }
}