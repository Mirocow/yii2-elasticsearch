<?php
namespace common\modules\elasticsearch\components\queries\Aggregation;

interface AggResultInterface
{
    public function getParsedResult();

    public function getResultsCarry();
}
