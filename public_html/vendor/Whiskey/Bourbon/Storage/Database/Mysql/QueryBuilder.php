<?php


namespace Whiskey\Bourbon\Storage\Database\Mysql;


use Closure;
use InvalidArgumentException;
use Whiskey\Bourbon\Storage\Database\Pagination;


/**
 * QueryBuilder class
 * @package Whiskey\Bourbon\Storage\Database\Mysql
 */
class QueryBuilder
{


    const _NO_REUSE = true;
    const MAX_LIMIT = '18446744073709551615';


    protected $_dependencies   = null;
    protected $_table          = null;
    protected $_data           = [];
    protected $_conditions     = [];
    protected $_options        = [];
    protected $_start_at       = 0;
    protected $_limit          = 0;
    protected $_paginate_limit = 0;
    protected $_cache          = 0;


    /**
     * Instantiate a new QueryBuilder object for chaining
     * @param Handler    $db       Handler object
     * @param Pagination $paginate Pagination object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Handler $db, Pagination $paginate)
    {
    
        if (!isset($db) OR
            !isset($paginate))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies           = new \stdClass();
        $this->_dependencies->db       = $db;
        $this->_dependencies->paginate = $paginate;

        $this->_limit          = static::MAX_LIMIT;
        $this->_paginate_limit = $this->_limit;

    }


    /**
     * Set a table to use for queries
     * @param  string $table Table name
     * @return self          QueryBuilder object for chaining
     */
    public function table($table = '')
    {

        $this->_table = $table;
        
        return $this;

    }


    /**
     * Specify a manual cache time for SELECT queries
     * @param  int  $ttl Time in seconds to cache results for
     * @return self      QueryBuilder object for chaining
     */
    public function cache($ttl = 0)
    {

        $this->_cache = (int)$ttl;
        
        return $this;

    }


    /**
     * Add a 'sub' argument if one exist
     * @param Closure $callback Callback to execute
     * @param string  $operator Logical operator
     */
    protected function _subWhere(Closure $callback, $operator = 'AND')
    {

        if ((is_object($callback) AND ($callback instanceof Closure)))
        {

            $query = new static($this->_dependencies->db,
                                $this->_dependencies->paginate);
            
            call_user_func_array($callback, [$query]);
            
            $this->_conditions[] = ['sub'  => $query->_conditions,
                                    'type' => strtoupper($operator)];

            unset($query);

        }

    }


    /**
     * Specify a condition
     * @param  string $field Field name
     * @param  string $value Field value
     * @return self          QueryBuilder object for chaining
     */
    public function where($field = '', $value = '')
    {

        if ((is_object($field) AND ($field instanceof Closure)))
        {

            $this->_subWhere($field, 'AND');

            return $this;

        }

        $this->_conditions[] = ['field' => $field,
                                'value' => $value,
                                'type'  => 'AND'];
        
        return $this;

    }


    /**
     * Specify a condition (alias of where() method)
     * @param  string $field Field name
     * @param  string $value Field value
     * @return self          QueryBuilder object for chaining
     */
    public function andWhere($field = '', $value = '')
    {

        return $this->where($field, $value);

    }


    /**
     * Specify an 'OR' condition
     * @param  string $field Field name
     * @param  string $value Field value
     * @return self          QueryBuilder object for chaining
     */
    public function orWhere($field = '', $value = '')
    {

        if ((is_object($field) AND ($field instanceof Closure)))
        {

            $this->_subWhere($field, 'OR');

            return $this;

        }

        $this->_conditions[] = ['field' => $field,
                                'value' => $value,
                                'type'  => 'OR'];
        
        return $this;

    }


    /**
     * Specify a 'not' condition
     * @param  string $field    Field name
     * @param  string $value    Field value
     * @param  string $operator Logical operator
     * @return self             QueryBuilder object for chaining
     */
    public function whereNot($field = '', $value = '', $operator = 'AND')
    {

        $this->_conditions[] = ['field'    => $field,
                                'value'    => $value,
                                'type'     => strtoupper($operator),
                                'operator' => '<>'];

        return $this;

    }


    /**
     * Specify an 'IN' condition
     * @param  string $field    Field name
     * @param  array  $value    Array of field values
     * @param  string $operator Logical operator
     * @return self             QueryBuilder object for chaining
     */
    public function whereIn($field = '', array $value = [], $operator = 'AND')
    {

        $this->_conditions[] = ['field'    => $field,
                                'value'    => $value,
                                'type'     => strtoupper($operator),
                                'operator' => 'IN'];

        return $this;

    }


    /**
     * Specify a 'NOT IN' condition
     * @param  string $field    Field name
     * @param  array  $value    Array of field values
     * @param  string $operator Logical operator
     * @return self             QueryBuilder object for chaining
     */
    public function whereNotIn($field = '', array $value = [], $operator = 'AND')
    {

        $this->_conditions[] = ['field'    => $field,
                                'value'    => $value,
                                'type'     => strtoupper($operator),
                                'operator' => 'NOT IN'];

        return $this;

    }


    /**
     * Specify an 'IS NULL' condition
     * @param  string $field    Field name
     * @param  string $operator Logical operator
     * @return self             QueryBuilder object for chaining
     */
    public function whereNull($field = '', $operator = 'AND')
    {

        $this->_conditions[] = ['field'    => $field,
                                'value'    => '',
                                'type'     => strtoupper($operator),
                                'operator' => 'IS NULL'];

        return $this;

    }


    /**
     * Specify an 'IS NOT NULL' condition
     * @param  string $field    Field name
     * @param  string $operator Logical operator
     * @return self             QueryBuilder object for chaining
     */
    public function whereNotNull($field = '', $operator = 'AND')
    {

        $this->_conditions[] = ['field'    => $field,
                                'value'    => '',
                                'type'     => strtoupper($operator),
                                'operator' => 'IS NOT NULL'];

        return $this;

    }


    /**
     * Specify a 'greater than' condition
     * @param  string $field    Field name
     * @param  string $value    Field value
     * @param  string $operator Logical operator
     * @return self             QueryBuilder object for chaining
     */
    public function whereGreaterThan($field = '', $value = '', $operator = 'AND')
    {

        $this->_conditions[] = ['field'    => $field,
                                'value'    => $value,
                                'type'     => strtoupper($operator),
                                'operator' => '>'];
                                  
        return $this;
  
    }


    /**
     * Specify a 'less than' condition
     * @param  string $field    Field name
     * @param  string $value    Field value
     * @param  string $operator Logical operator
     * @return self             QueryBuilder object for chaining
     */
    public function whereLessThan($field = '', $value = '', $operator = 'AND')
    {

        $this->_conditions[] = ['field'    => $field,
                                'value'    => $value,
                                'type'     => strtoupper($operator),
                                'operator' => '<'];

        return $this;

    }


    /**
     * Specify a 'contains' condition
     * @param  string $field    Field name
     * @param  string $value    Partial field value
     * @param  string $operator Logical operator
     * @return self             QueryBuilder object for chaining
     */
    public function whereLike($field = '', $value = '', $operator = 'AND')
    {

        $this->_conditions[] = ['field'   => $field,
                                'value'   => $value,
                                'type'    => strtoupper($operator),
                                'compare' => 'LIKE'];
       
        return $this;

    }


    /**
     * Specify a 'does not contain' condition
     * @param  string $field    Field name
     * @param  string $value    Partial field value
     * @param  string $operator Logical operator
     * @return self             QueryBuilder object for chaining
     */
    public function whereNotLike($field = '', $value = '', $operator = 'AND')
    {

        $this->_conditions[] = ['field'   => $field,
                                'value'   => $value,
                                'type'    => strtoupper($operator),
                                'compare' => 'NOT LIKE'];

        return $this;

    }


    /**
     * Specify a condition where two columns are equal
     * @param  string $field    Field name
     * @param  string $value    Partial field value
     * @param  string $operator Logical operator
     * @return self             QueryBuilder object for chaining
     */
    public function whereFieldsEqual($field = '', $value = '', $operator = 'AND')
    {

        $this->_conditions[] = ['field'   => $field,
                                'value'   => $value,
                                'type'    => strtoupper($operator),
                                'compare' => 'WHERE_FIELDS_EQUAL'];

        return $this;

    }


    /**
     * Specify an ordering rule
     * @param  string $field     Field name
     * @param  string $direction Ordering direction (either ASC or DESC)
     * @return self              QueryBuilder object for chaining
     */
    public function orderBy($field = '', $direction = 'ASC')
    {

        $this->_options['order_by'][] = ['field'     => $field,
                                         'direction' => strtoupper($direction)];

        return $this;

    }


    /**
     * Specify an offset
     * @param  int  $offset Number of rows to skip
     * @return self         QueryBuilder object for chaining
     */
    public function startAt($offset = 0)
    {
    
        $this->_start_at = $offset;

        $this->_options['limit'] = $this->_start_at . ', ' . $this->_limit;
        
        return $this;

    }


    /**
     * Specify the limit
     * @param  int  $limit Number of rows to return
     * @return self        QueryBuilder object for chaining
     */
    public function fetch($limit = 1)
    {

        $this->_limit = $limit;
        
        $this->_options['limit'] = $this->_start_at . ', ' . $this->_limit;
        
        return $this;

    }


    /**
     * Get the query pagination limit
     * @return int Query pagination limit
     */
    public function getPaginateLimit()
    {

        return $this->_paginate_limit;

    }


    /**
     * Paginate the results
     * @param  int  $limit Number of rows to return
     * @return self        QueryBuilder object for chaining
     */
    public function paginate($limit = 10)
    {

        $this->_paginate_limit = $limit;

        $offset = $this->_dependencies->paginate->getInfoForDbQuery($this);

        $this->startAt($offset);
        $this->fetch($limit);

        return $this;

    }


    /**
     * Specify a field to group by
     * @param  string $field Field to group by
     * @return self          QueryBuilder object for chaining
     */
    public function groupBy($field = '')
    {

        $this->_options['group_by'] = $field;
        
        return $this;
    
    }


    /**
     * Specify data to be used with an INSERT or UPDATE query
     * @param  mixed  $field Field name (or array of field/value pairs)
     * @param  string $value Value to insert or update with
     * @return self          QueryBuilder object for chaining
     */
    public function data($field = '', $value = '')
    {

        /*
         * If the first/only argument is an array call this method on each of
         * its elements
         */
        if (is_array($field))
        {

            foreach ($field as $var => $value)
            {
                $this->data($var, $value);
            }

        }

        /*
         * If not, add the field/value to the data list
         */
        else
        {
            $this->_data[$field] = $value;
        }
        
        return $this;

    }


    /**
     * Specify JOIN to be used with a SELECT query
     * @param  string $type    Type of join (e.g. LEFT JOIN, INNER JOIN, etc.)
     * @param  string $table   Name of table to join with
     * @param  string $compare Field in last table to compare
     * @param  string $against Field in joined table to compare against
     * @param  string $with    Optional table to join with
     * @return self            QueryBuilder object for chaining
     */
    public function join($type = 'LEFT JOIN', $table = '', $compare = '', $against = '', $with = '')
    {

        $this->_options['join'][] = ['type'    => $type,
                                     'table'   => $table,
                                     'compare' => $compare,
                                     'against' => $against,
                                     'with'    => $with];

        return $this;

    }


    /**
     * Specify LEFT JOIN to be used with a SELECT query
     * @param  string $table   Name of table to join with
     * @param  string $compare Field in last table to compare
     * @param  string $against Field in joined table to compare against
     * @param  string $with    Optional table to join with
     * @return self            QueryBuilder object for chaining
     */
    public function leftJoin($table = '', $compare = '', $against = '', $with = '')
    {

        return $this->join('LEFT JOIN', $table, $compare, $against, $with);

    }


    /**
     * Specify RIGHT JOIN to be used with a SELECT query
     * @param  string $table   Name of table to join with
     * @param  string $compare Field in last table to compare
     * @param  string $against Field in joined table to compare against
     * @param  string $with    Optional table to join with
     * @return self            QueryBuilder object for chaining
     */
    public function rightJoin($table = '', $compare = '', $against = '', $with = '')
    {

        return $this->join('RIGHT JOIN', $table, $compare, $against, $with);

    }


    /**
     * Specify INNER JOIN to be used with a SELECT query
     * @param  string $table   Name of table to join with
     * @param  string $compare Field in last table to compare
     * @param  string $against Field in joined table to compare against
     * @param  string $with    Optional table to join with
     * @return self            QueryBuilder object for chaining
     */
    public function innerJoin($table = '', $compare = '', $against = '', $with = '')
    {
    
        return $this->join('INNER JOIN', $table, $compare, $against, $with);
    
    }


    /**
     * Specify LEFT OUTER JOIN to be used with a SELECT query
     * @param  string $table   Name of table to join with
     * @param  string $compare Field in last table to compare
     * @param  string $against Field in joined table to compare against
     * @param  string $with    Optional table to join with
     * @return self            QueryBuilder object for chaining
     */
    public function leftOuterJoin($table = '', $compare = '', $against = '', $with = '')
    {
    
        return $this->join('LEFT OUTER JOIN', $table, $compare, $against, $with);
    
    }


    /**
     * Specify RIGHT OUTER JOIN to be used with a SELECT query
     * @param  string $table   Name of table to join with
     * @param  string $compare Field in last table to compare
     * @param  string $against Field in joined table to compare against
     * @param  string $with    Optional table to join with
     * @return self            QueryBuilder object for chaining
     */
    public function rightOuterJoin($table = '', $compare = '', $against = '', $with = '')
    {

        return $this->join('RIGHT OUTER JOIN', $table, $compare, $against, $with);

    }


    /**
     * Terminating method to perform a SELECT query
     * @param  string ... Multiple field names to return
     * @return array      Array of results
     */
    public function select()
    {

        return $this->_dependencies->db->select($this->_table,
                                                $this->_conditions,
                                                $this->_options,
                                                func_get_args(),
                                                $this->_cache);

    }


    /**
     * Terminating method to perform a CREATE VIEW query
     * @param  string ... View name and multiple field names to return
     * @return bool       Whether the view was successfully created
     */
    public function createView()
    {

        $arguments = func_get_args();
        $view_name = array_shift($arguments);

        return $this->_dependencies->db->createView($view_name,
                                                    $this->_table,
                                                    $this->_options,
                                                    $arguments);

    }


    /**
     * Terminating method to perform an INSERT query
     * @return int Insert ID
     */
    public function insert()
    {

        return $this->_dependencies->db->insert($this->_table, $this->_data);

    }


    /**
     * Terminating method to perform an UPDATE query
     * @return int Number of affected rows
     */
    public function update()
    {

        return $this->_dependencies->db->update($this->_table,
                                                $this->_data,
                                                $this->_conditions);

    }


    /**
     * Terminating method to perform a DELETE query
     * @return int Number of affected rows
     */
    public function delete()
    {

        return $this->_dependencies->db->delete($this->_table, $this->_conditions);

    }


    /**
     * Terminating method to perform a SELECT query and return from the first row
     * the value of a particular field
     * @param  string $field Field of target value
     * @return string        First $field field
     * @throws InvalidArgumentException if the requested field does not exist
     */
    public function getField($field = '')
    {

        $result = $this->select($field);

        foreach ($result as $value)
        {

            if (is_array($field))
            {
                $field = reset($field);
            }

            if (isset($value[$field]))
            {
                return $value[$field];
            }

            throw new InvalidArgumentException('Column \'' . $field . '\' does not exist in results');

        }

        return null;

    }


    /**
     * Terminating method to count the results of a SELECT query
     * @return int Number of rows in query
     */
    public function count()
    {

        $count  = $this->select(['COUNT(*)' => 'count']);
        $result = 0;
        
        /*
         * If the results have been grouped, return the number of groups
         */
        if (count($count) > 1)
        {
            return count($count);
        }

        /*
         * If not, return the single value
         */
        $count = reset($count);
        
        if (isset($count['count']))
        {
            return (int)$count['count'];
        }

        return $result;

    }


    /**
     * Check if a select() call would find a result
     * @return bool Whether or not records would be returned
     */
    public function exists()
    {

        if ($this->count())
        {
            return true;
        }

        return false;

    }


    /**
     * Terminating method to sum the contents of a column
     * @param  string $column Name of column to sum
     * @return int            Sum of column results
     * @throws InvalidArgumentException if a column has not been provided
     */
    public function sum($column = '')
    {

        if ((string)$column === '')
        {
            throw new InvalidArgumentException('No column provided');
        }

        $sum    = $this->select(['SUM(`' . $column . '`)' => 'sum']);
        $result = 0;

        foreach ($sum as $row)
        {

            if (isset($row['sum']))
            {
                $result += $row['sum'];
            }

        }

        return $result;

    }


    /**
     * Terminating method to get the average value of a column
     * @param  string $column Name of column to get the average of
     * @return int            Average value of column results
     * @throws InvalidArgumentException if a column has not been provided
     */
    public function average($column = '')
    {

        if ((string)$column === '')
        {
            throw new InvalidArgumentException('No column provided');
        }

        $average   = $this->select(['AVG(`' . $column . '`)' => 'average']);
        $result    = 0;
        $divide_by = count($average) ?: 1;

        foreach ($average as $row)
        {

            if (isset($row['average']))
            {
                $result += $row['average'];
            }

        }

        return ($result / $divide_by);

    }


    /**
     * Terminating method to get the minimum value of a column
     * @param  string $column Name of column to get the minimum of
     * @return int            Minimum value of column results
     * @throws InvalidArgumentException if a column has not been provided
     */
    public function min($column = '')
    {

        if ((string)$column === '')
        {
            throw new InvalidArgumentException('No column provided');
        }

        $minimum = $this->select(['MIN(`' . $column . '`)' => 'minimum']);
        $result  = null;

        foreach ($minimum as $row)
        {

            if (isset($row['minimum']) AND
                (is_null($result) OR $row['minimum'] < $result))
            {
                $result = $row['minimum'];
            }

        }

        return is_null($result) ? 0 : $result;

    }


    /**
     * Terminating method to get the maximum value of a column
     * @param  string $column Name of column to get the maximum of
     * @return int            Maximum value of column results
     * @throws InvalidArgumentException if a column has not been provided
     */
    public function max($column = '')
    {

        if ((string)$column === '')
        {
            throw new InvalidArgumentException('No column provided');
        }

        $maximum = $this->select(['MAX(`' . $column . '`)' => 'maximum']);
        $result  = null;

        foreach ($maximum as $row)
        {

            if (isset($row['maximum']) AND
                (is_null($result) OR $row['maximum'] > $result))
            {
                $result = $row['maximum'];
            }

        }

        return is_null($result) ? 0 : $result;

    }


    /**
     * Terminating method to get a random set of results
     * @param  int   $limit Number of records to return
     * @return array        Array of results
     */
    public function random($limit = 10)
    {

        return $this->fetch($limit)
                    ->orderBy('RAND()')
                    ->select();

    }


    /**
     * Convert a multidimensional array to a CSV, taking the first row's keys as
     * the column names
     * @param  array  $array Input array
     * @return string        CSV string
     * @throws InvalidArgumentException if input is not a valid array
     */
    protected function _arrayToCsv(array $array = [])
    {

        if (!is_array($array))
        {
            throw new InvalidArgumentException('Input is not an array');
        }

        if (empty($array))
        {
            return '';
        }

        $header = reset($array);

        if (!is_array($header))
        {
            throw new InvalidArgumentException('Input array does not appear to be valid');
        }

        $header = array_keys($header);
        $result = $this->_makeCsv($header);

        foreach ($array as $row)
        {

            $result .= "\n" . $this->_makeCsv($row);

        }

        return $result;

    }


    /**
     * Convert a multidimensional array to a Bootstrap-compatible table, taking
     * the first row's keys as the column names
     * @param  array  $array Input array
     * @return string        Bootstrap-compatible HTML table string
     * @throws InvalidArgumentException if input is not a valid array
     */
    protected function _arrayToTable(array $array = [])
    {

        if (!is_array($array))
        {
            throw new InvalidArgumentException('Input is not an array');
        }

        if (empty($array))
        {
            return '';
        }

        $header = reset($array);

        if (!is_array($header))
        {
            throw new InvalidArgumentException('Input array does not appear to be valid');
        }

        $header = array_keys($header);
        $result = '<table class="table"><thead><tr>';

        foreach ($header as $column_name)
        {
            $result .= '<td>' . $column_name . '</td>';
        }

        $result .= '</tr></thead><tbody>';

        foreach ($array as $row)
        {

            $result .= '<tr>';

            foreach ($row as $column_value)
            {
                $result .= '<td>' . $column_value . '</td>';
            }

            $result .= '</tr>';

        }

        $result .= '</tbody></table>';

        return $result;

    }


    /**
     * Convert an array to a CSV line
     * @param  array  $array     Single-dimension array
     * @param  string $delimiter Column delimiter
     * @param  string $quotes    Quote character
     * @return string            CSV string
     */
    protected function _makeCsv(array $array = [], $delimiter = ',', $quotes = '"')
    {

        $temp_file = fopen('php://temp', 'r+');

        fputcsv($temp_file, $array, $delimiter, $quotes);
        rewind($temp_file);

        $csv_line = fread($temp_file, 1048576);

        fclose($temp_file);

        return rtrim($csv_line, "\n");

    }


}