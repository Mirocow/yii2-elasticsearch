<?php
namespace mirocow\elasticsearch\components\queries\Aggregation;

interface AggResultInterface
{
    public function getParsedResult();

    public function getResultsCarry();
}
