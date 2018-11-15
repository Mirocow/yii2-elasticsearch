<?php
namespace mirocow\elasticsearch\contracts;

/**
 * Interface PopulateInterface
 * @package mirocow\elasticsearch\contracts
 */
interface PopulateInterface
{

    public function setResult(&$result = []);

    /**
     * Converts the raw query results into the format as specified by this query.
     * This method is internally used to convert the data fetched from database
     * into the format as required by this query.
     * @return array the converted query result
     * @since 2.0.4
     */
    public function populate();

    /**
     * Finds records corresponding to one or multiple relations and populates them into the primary models.
     * @param array $with a list of relations that this query should be performed with. Please
     * refer to [[with()]] for details about specifying this parameter.
     * @param array|ActiveRecord[] $models the primary models (can be either AR instances or arrays)
     */
    public function findWith($with, &$models);

    /**
     * Executes the query and returns all results as an array.
     * If this parameter is not given, the `elasticsearch` application component will be used.
     * @return array the query results. If the query results in nothing, an empty array will be returned.
     */
    public function all();

    /**
     * Executes the query and returns all results as an array.
     * If this parameter is not given, the `elasticsearch` application component will be used.
     * @return array the query results. If the query results in nothing, an empty array will be returned.
     */
    public function one();

    /**
     * @param $select
     */
    public function select($select);

    /**
     * @inheritdoc
     */
    public function search();
}