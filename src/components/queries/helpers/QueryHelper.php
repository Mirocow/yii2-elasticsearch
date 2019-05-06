<?php
namespace mirocow\elasticsearch\components\queries\helpers;

use yii\helpers\ArrayHelper;

class QueryHelper
{
    /**
     * @example QueryHelper::bool(QueryHelper::match(), QueryHelper::match(), QueryHelper::match(), QueryHelper::match())
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-filter-context.html
     * @param array $filterQueries
     * @param array $mustQueries
     * @param array $shouldQueries
     * @param array $mustNotQueries
     * @return array
     */
    public static function bool($filterQueries = [], $mustQueries = [], $shouldQueries = [], $mustNotQueries = []) :array
    {
        $out = [];
        if (!empty($filterQueries)) {
            $out['bool']['filter'] = $filterQueries;
        }
        if (!empty($mustQueries)) {
            $out['bool']['must'] = $mustQueries;
        }
        if (!empty($shouldQueries)) {
            $out['bool']['should'] = $shouldQueries;
        }
        if (!empty($mustNotQueries)) {
            $out['bool']['must_not'] = $mustNotQueries;
        }
        return $out;
    }

    /**
     * @example QueryHelper::filter(QueryHelper::match())
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-bool-query.html#_scoring_with_literal_bool_filter_literal
     * @param array $matchQueries
     * @return array
     */
    public static function filter(array $matchQueries) :array
    {
        return self::bool($matchQueries);
    }

    /**
     * @example QueryHelper::must(QueryHelper::match())
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-bool-query.html
     * @param array $matchQueries
     * @return array
     */
    public static function must(array $matchQueries) :array
    {
        return self::bool([], $matchQueries);
    }

    /**
     * @example QueryHelper::should(QueryHelper::match())
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-bool-query.html
     * @param array $matchQueries
     * @return array
     */
    public static function should(array $matchQueries) :array
    {
        return self::bool([], [], $matchQueries);
    }

    /**
     * @example QueryHelper::mustNot(QueryHelper::match())
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-bool-query.html
     * @param array $matchQueries
     * @return array
     */
    public static function mustNot(array $matchQueries) :array
    {
        return self::bool([], [], [], $matchQueries);
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-terms-query.html
     * @param string $field
     * @param string[]|int[] $terms
     * @return object
     */
    public static function terms($field, $terms = []) :\stdClass
    {
        return (object) [
            'terms' => [
                $field => $terms
            ]
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-term-query.html
     * @param string $field
     * @param string $term
     * @return object
     */
    public static function term($field, $term) :\stdClass
    {
        return (object) [
            'term' => [
                $field => $term
            ]
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-range-query.html
     * @param string $field
     * @param string|int $gte greater than or equal
     * @param string|int $lte less than or equal
     * @param array $options options to pass into the range query
     * @return object
     */
    public static function range($field, $gte = '', $lte = '', $options = []) :\stdClass
    {
        if ($gte !== '') {
            $options['gte'] = $gte;
        }
        if ($lte !== '') {
            $options['lte'] = $lte;
        }
        return (object) [
            'range' => [
                $field => $options
            ]
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/nested.html
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-nested-query.html
     * @param string $path
     * @param string $query
     * @return object
     */
    public static function nested($path, $query = '') :\stdClass
    {
        return (object) [
            'nested' => [
                'path' => $path,
                'query' => self::query($query)
            ]
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-wildcard-query.html
     * @param string $field
     * @param string $searchQuery
     * @return object
     */
    public static function fullWildcard($field, $searchQuery) :\stdClass
    {
        return (object) [
            'wildcard' => [
                $field => "*$searchQuery*"
            ]
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-wildcard-query.html
     * Expects * somewhere in the string, if at the end, might as well just use prefix instead
     * @param string $field
     * @param string $match
     * @return object
     */
    public static function wildcard($field, $match, $searchQuery = []) :\stdClass
    {
        $query = [
            $field => $match,
        ];

        $query = ArrayHelper::merge($query, $searchQuery);

        return (object) [
            'wildcard' => $query
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-regexp-query.html
     * The regexp query allows you to use regular expression term queries.
     * @param string $field
     * @param string $match
     * @return object
     */
    public static function regexp($field, $match, $searchQuery = []) :\stdClass
    {
        $query = [
            $field => $match,
        ];

        $query = ArrayHelper::merge($query, $searchQuery);

        return (object) [
            'regexp' => $query
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-suggesters.html#global-suggest
     * @param string $field
     * @param string $searchQuery
     * @return object
     */
    public static function suggest($field, $searchQuery) :\stdClass
    {
        $prefix = self::prefix($field, $searchQuery);
        return (object) [
            'suggest' => $prefix
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-prefix-query.html
     * @param string $field
     * @param string|array $searchTerms
     * @return object
     */
    public static function prefix($field, $searchTerms) :\stdClass
    {
        return (object) [
            'prefix' => [
                $field => $searchTerms
            ]
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-match-query.html
     * @param $field
     * @param $match
     * @param string $type
     *      match - queries accept text/numerics/dates, analyzes them, and constructs a query.
     *      match_phrase - The match_phrase query analyzes the text and creates a phrase query out of the analyzed text.
     *      match_phrase_prefix - The match_phrase_prefix is the same as match_phrase, except that it allows for prefix matches on the last term in the text.
     *      multi_match - @see self::multiMatch()
     *      common - @see self::common()
     *      simple_query_string - A query that uses the SimpleQueryParser to parse its context.
     *                            Unlike the regular query_string query, the simple_query_string query will never throw an exception, and discards invalid parts of the query.
     * @param string $searchQuery
     * @return object
     */
    public static function match($field, $match, $type = 'match', $searchQuery = []) :\stdClass
    {
        $query = [
            $field => $match,
        ];

        $searchQuery = ArrayHelper::merge($query, $searchQuery);

        return (object) [
            $type => $searchQuery
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-multi-match-query.html
     * @param array $fields
     * @param string $query
     * @param string $type
     *    best_fields - (default) Finds documents which match any field, but uses the _score from the best field. See best_fields.
     *    most_fields - Finds documents which match any field and combines the _score from each field. See most_fields.
     *    cross_fields - Treats fields with the same analyzer as though they were one big field. Looks for each word in any field. See cross_fields.
     *    phrase - Runs a [[match_phrase]] query on each field and combines the _score from each field. See phrase and phrase_prefix.
     *    phrase_prefix - Runs a [[match_phrase_prefix]] query on each field and combines the _score from each field. See phrase and phrase_prefix.
     * @param string $searchQuery
     * @return object
     */
    public static function multiMatch($fields, $query, $type = 'phrase', $searchQuery = []) :\stdClass
    {
        $query = [
            'query' => $query,
            'type' => $type,
            'fields' => $fields,
        ];

        $query = ArrayHelper::merge($query, $searchQuery);

        return (object) [
            'multi_match' => $query,
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-common-terms-query.html
     * @param $query
     * @param array $searchQuery
     * @return \stdClass
     */
    public static function common($query, $searchQuery = []) :\stdClass
    {
        $query = [
            'query' => $query,
        ];

        $body = ArrayHelper::merge($body, $searchQuery);

        return (object) [
            'common' => [
                'body' => $body,
            ]
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-match-all-query.html
     * @param string $query
     * @return object
     */
    public static function query($query = '')
    {
        return empty($query) ? ["match_all" => (object) []] : $query;
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-query-string-query.html
     * @param string $query
     * @param string $default_field
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-query-string-query.html#_default_field
     *
     * @param array $searchQuery
     * @return object
     */
    public static function query_string($query = '', $default_field = '_all', $searchQuery = []) :\stdClass
    {
        $query = [
            'query' => $query,
        ];

        if($default_field){
            $query['default_field'] = $default_field;
        }

        $query = ArrayHelper::merge($query, $searchQuery);

        return (object) [
            'query_string' => $query
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-dis-max-query.html
     * @param array $queries
     * @param array $searchQuery
     * @return object
     */
    public static function disMax($queries = [], $searchQuery = []) :\stdClass
    {
        $query = [
            'queries' => $queries,
        ];

        $query = ArrayHelper::merge($query, $searchQuery);

        return (object) [
            'dis_max' => $query,
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-function-score-query.html
     * @param array $queries
     * @param array $searchQuery
     * @return object
     */
    public static function functionScore($query = [], $searchQuery = []) :\stdClass
    {
        $query = [
            'query' => $queries,
        ];

        $query = ArrayHelper::merge($query, $searchQuery);

        return (object) [
            'function_score' => $query,
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/mapping-date-format.html
     * @param $format
     * @return object
     */
    public static function format($format)
    {
        return (object) [
            'format' => $format,
        ];
    }

    /**
     * @param $field
     * @return object
     */
    public static function exists($field) :\stdClass
    {
        return (object) [
            'exists' => [
                'field' => $field,
            ],
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-from-size.html
     * @param integer|null $limit
     * @return object
     */
    public static function limit($limit = null)
    {
        return (object) [
            'size' => (int) $limit
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-from-size.html
     * @param integer|null $offset
     * @return array|object
     */
    public static function offset($offset = null)
    {
        return (object) [
            'from' => (int) $offset
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-request-sort.html
     * @param $columns
     *
     * @return array
     */
    public static function sortBy($columns) :array
    {
        return self::buildOrderBy($columns);
    }

    /**
     * https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-sort.html#_sort_mode_option
     * @param string $column
     * @param int $direction
     * @param string $mode
     * @return array
     */
    public static function sortByMode(string $column, int $direction = SORT_ASC, $mode = 'sum') :array
    {
        return [
            $column => (object) [
                'order' => $direction === SORT_DESC ? 'desc' : 'asc',
                'mode' => $mode,
            ],
        ];
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-sort.html#_sort_mode_option
     * @param string $column
     * @param int $direction
     * @param string $mode
     * @return array
     */
    public static function sortByCount(string $column, int $direction = SORT_ASC, $mode = 'sum') :array
    {
        return self::sortByMode($column, $direction, 'sum');
    }

    /**
     * Adds order by condition to the query
     * @param $columns Examples: ['field' => SORT_ASC]; ['field' => ["order" => "asc", "mode" => "avg"]]
     * @return array|object
     */
    private static function buildOrderBy($columns) :array
    {
        $orders = [];
        foreach ($columns as $name => $direction) {
            if (is_string($direction)) {
                $column = $direction;
                $direction = SORT_ASC;
            } else {
                $column = $name;
            }

            if ($column === '_id') {
                $column = '_uid';
            }

            // allow elasticsearch extended syntax as described in http://www.elastic.co/guide/en/elasticsearch/guide/master/_sorting.html
            if (is_array($direction)) {
                $orders[] = (object)[$column => $direction];
            } else {
                $orders[] = (object)[$column => ($direction === SORT_DESC? 'desc': 'asc')];
            }
        }

        return $orders;
    }

    /**
     * @param string $script
     * @param int $direction
     * @param string $language
     *
     * @return array
     */
    public static function sortByScript(string $script = '', int $direction = SORT_ASC, $language = 'painless') :array
    {
        return ScriptHelper::sort($script, $direction, $language);
    }

    /**
     * @param string $script
     * @param array $params
     * @param string $language
     *
     * @return array
     */
    public static function queryByScript(string $script = '', $params = [], $language = 'painless') :array
    {
        return [
            'script' => (object) ScriptHelper::query($script, $params, $language),
        ];
    }
}
