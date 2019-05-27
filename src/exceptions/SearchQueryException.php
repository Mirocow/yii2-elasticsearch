<?php
namespace mirocow\elasticsearch\exceptions;

use yii\base\ErrorException;

class SearchQueryException extends ErrorException
{
    /**
     * @var iarray.
     */
    public $requestQuery;


    /**
     * SearchQueryException constructor.
     *
     * @param string $requestQuery
     * @param string|null $message
     * @param int $code
     * @param string $filename
     * @param int $lineno
     * @param \Exception|null $previous
     */
    public function __construct(string $requestQuery, string $message = null, $code = 0, $filename = __FILE__, $lineno = __LINE__, \Exception $previous = null)
    {
        $this->requestQuery = $requestQuery;
        parent::__construct($message, $code, 1, $filename, $lineno, $previous);
    }
}
