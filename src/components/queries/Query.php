<?php
namespace mirocow\elasticsearch\components\queries;

/**
 * Class Query
 * @package mirocow\elasticsearch\components\queries
 */
class Query extends \ArrayObject
{
    /**
     * @return string
     */
    public function __toString()
    {
        return Json::encode($this);
    }
}