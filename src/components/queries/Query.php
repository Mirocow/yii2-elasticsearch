<?php
namespace mirocow\elasticsearch\components\queries;

use mirocow\elasticsearch\contracts\QueryInterface;

/**
 * Class Query
 * @package mirocow\elasticsearch\components\queries
 */
class Query extends \ArrayObject implements QueryInterface
{
    /**
     * @return string
     */
    public function __toString()
    {
        return Json::encode($this);
    }

}