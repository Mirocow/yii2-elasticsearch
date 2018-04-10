<?php
namespace mirocow\elasticsearch\components\queries\Aggregation;

class AggResult implements AggResultInterface
{
    /** @var array */
    protected $parsedResult;
    /** @var array */
    protected $resultsCarry;

    public function __construct($parsedResult, $resultsCarry)
    {
        $this->parsedResult = $parsedResult;
        $this->resultsCarry = $resultsCarry;
    }

    public function getParsedResult()
    {
        return $this->parsedResult;
    }

    public function getResultsCarry()
    {
        return $this->resultsCarry;
    }
}
