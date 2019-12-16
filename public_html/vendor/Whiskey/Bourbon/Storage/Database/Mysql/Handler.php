<?php


namespace Whiskey\Bourbon\Storage\Database\Mysql;


use mysqli;
use stdClass;
use Exception;
use Closure;
use InvalidArgumentException;
use Whiskey\Bourbon\Exception\MissingDependencyException;
use Whiskey\Bourbon\Exception\Storage\Database\InvalidConnectionException;
use Whiskey\Bourbon\Exception\Storage\Database\InvalidQueryException;
use Whiskey\Bourbon\Exception\Storage\Database\InvalidSchemaException;
use Whiskey\Bourbon\Exception\Storage\Database\UnknownDuplicationException;
use Whiskey\Bourbon\Exception\Storage\UnwritableFileException;
use Whiskey\Bourbon\Storage\Cache\Handler as Cache;
use Whiskey\Bourbon\Storage\Database\Pagination;
use Whiskey\Bourbon\Storage\Database\SchemaBuilder;


/**
 * MySql Handler class
 * @package Whiskey\Bourbon\Storage\Database\Mysql
 */
class Handler
{


    protected $_dependencies         = null;
    protected $_mysqli               = null;
    protected $_connections          = [];
    protected $_connection_names     = [];
    protected $_connection_details   = [];
    protected $_last_query           = '';
    protected $_last_values          = [];
    protected $_last_types           = '';
    protected $_db_cache_prefix_base = '_bourbon_db_cache_';
    protected $_db_cache_prefix      = '';


    private $_instances = [];


    /**
     * Instantiate the MySQL database handler
     * @param Cache      $cache    Cache object
     * @param Pagination $paginate Pagination object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Cache $cache, Pagination $paginate)
    {

        if (!isset($cache) OR
            !isset($paginate))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies           = new stdClass();
        $this->_dependencies->cache    = $cache;
        $this->_dependencies->paginate = $paginate;

    }


    /**
     * Add a database connection
     * @param  string $name    Name to identify the connection
     * @param  array  $details Array of connection details
     * @return bool            Whether the connection was successfully made
     */
    public function add($name = '', array $details = [])
    {

        /*
         * Check that all details exist
         */
        if ($name AND
            !in_array($name, $this->_connections) AND
            is_array($details) AND
            isset($details['username']) AND
            isset($details['password']) AND
            isset($details['database']) AND
            $details['username'] AND
            $details['database'])
        {

            $this->_connection_names[] = $name;

            /*
             * Fill in any missing connection details
             */
            if (!isset($details['port']) OR !$details['port'])
            {
                $details['port'] = ini_get('mysqli.default_port');
            }

            if (!isset($details['socket']) OR !$details['socket'])
            {
                $details['socket'] = ini_get('mysqli.default_socket');
            }

            if (!isset($details['host']) OR !$details['host'])
            {
                $details['host'] = '127.0.0.1';
            }

            if (!isset($details['charset']) OR !$details['charset'])
            {
                $details['charset'] = 'utf8mb4';
            }

            /*
             * Attempt the connection
             */
            try
            {

                $connection = $this->_connect($details);

                /*
                 * If everything is present and accounted for, add it to the
                 * connections list
                 */
                if ($connection !== false)
                {

                    $this->_connections[$name] = ['connection'         => $connection,
                                                  'connection_details' => $details];

                    /*
                     * If this is the first connection, make it the default
                     */
                    if (!$this->_mysqli)
                    {
                        $this->setDefaultConnection($name);
                    }

                    return true;

                }

            }

            catch (Exception $exception)
            {
                return false;
            }

        }

        return false;

    }


    /**
     * Attempt to make a database connection
     * @param  array  $details Array of connection details
     * @return object          MySQLi object
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidArgumentException if connection details are not provided
     * @throws InvalidConnectionException if a connection could not be made
     */
    protected function _connect(array $details = [])
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!is_array($details) OR
            !isset($details['username']) OR
            !isset($details['password']) OR
            !isset($details['database']))
        {
            throw new InvalidArgumentException('Missing connection details');
        }

        try
        {

            $result = new mysqli($details['host'],
                                 $details['username'],
                                 $details['password'],
                                 $details['database'],
                                 $details['port'],
                                 $details['socket']);

            if ($result AND !$result->connect_error)
            {

                /*
                 * Set some options and return
                 */
                $result->set_charset($details['charset']);
                $result->query('SET sql_mode = \'\';');

                return $result;

            }

        }

        catch (Exception $exception)
        {
            throw new InvalidConnectionException($exception->getMessage(), 0, $exception);
        }

        /*
         * If no exception was thrown but a valid connection was still not made
         */
        throw new InvalidConnectionException('Unknown connection error');

    }


    /**
     * Get an array of all database connection names
     * @return array Array of database connection names
     */
    public function getConnectionNames()
    {

        return $this->_connection_names;

    }
    
    
    /**
     * Get the current connection details
     * @author Joseph Middleton
     * @return array Array of connection details
     */
    public function getConnectionDetails()
    {

        return $this->_connection_details;

    }


    /**
     * Return a Handler object with the connection specified
     * @param  string  $connection Connection name
     * @return Handler             Handler object
     * @throws InvalidConnectionException if the requested connection is not valid
     */
    public function swap($connection = '')
    {

        /*
         * Check that the requested connection exists
         */
        if (isset($this->_connections[$connection]))
        {

            /*
             * If an instance doesn't already exist, create and store it
             */
            if (!isset($this->_instances[$connection]))
            {

                $new_db_connection = new static($this->_dependencies->cache, $this->_dependencies->paginate);
                
                $new_db_connection->_inheritDefaultConnection($this->_connections, $connection);
                
                $this->_instances[$connection] = $new_db_connection;

            }
            
            return $this->_instances[$connection];
        
        }

        throw new InvalidConnectionException('Invalid connection');

    }


    /**
     * Change the default connection
     * @param string $connection_name Connection name
     * @throws InvalidConnectionException if the requested connection is not valid
     */
    public function setDefaultConnection($connection_name = '')
    {

        if (isset($this->_connections[$connection_name]))
        {
            $this->_mysqli             = $this->_connections[$connection_name]['connection'];
            $this->_connection_details = $this->_connections[$connection_name]['connection_details'];
            $this->_db_cache_prefix    = $this->_db_cache_prefix_base . '_' . md5($connection_name) . '_';
        }

        else
        {
            throw new InvalidConnectionException('Invalid connection');
        }

    }


    /**
     * Set a MySQLi object as the default connection
     * @param array  $connections     Array of connections
     * @param string $connection_name Connection name
     * @throws InvalidConnectionException if the requested connection is not valid
     */
    protected function _inheritDefaultConnection(array $connections = [], $connection_name = '')
    {

        if (!is_null($connections[$connection_name]))
        {
            $this->_connections        = $connections;
            $this->_mysqli             = $connections[$connection_name]['connection'];
            $this->_connection_details = $connections[$connection_name]['connection_details'];
            $this->_db_cache_prefix    = $this->_db_cache_prefix_base . '_' . md5($connection_name) . '_';
        }

        else
        {
            throw new InvalidConnectionException('Invalid connection');
        }

    }


    /**
     * Check whether a database connection has been made
     * @return bool Boolean value reflecting connection status
     * @deprecated
     */
    public function connected()
    {

        return $this->isConnected();

    }


    /**
     * Check whether a database connection has been made
     * @return bool Boolean value reflecting connection status
     * @throws MissingDependencyException if MySQLi is missing
     */
    public function isConnected()
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->_mysqli)
        {
            return false;
        }

        return true;

    }


    /**
     * Get the names of the tables in the database
     * @return array Array of table names
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     */
    public function getTables()
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        $result = [];
        $tables = $this->raw('SELECT DISTINCT `TABLE_NAME` AS `table_name` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA` = ?',
                             [$this->_connection_details['database']]);

        foreach ($tables as $table)
        {
            $result[] = $table['table_name'];
        }

        return $result;

    }


    /**
     * Duplicate the current database into another database
     * @param string $target_connection Connection name of target database
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidArgumentException if a target database name has not been provided
     * @throws InvalidConnectionException if the connection is not valid
     * @throws UnknownDuplicationException if duplication fails (throws caught exception)
     */
    public function duplicate($target_connection = '')
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if ($target_connection == '')
        {
            throw new InvalidArgumentException('Invalid target database');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        try
        {

            /*
             * Get creation queries for source tables
             */
            $tables           = $this->getTables();
            $creation_queries = ['tables' => [], 'views' => []];

            foreach ($tables as $table)
            {

                $create_query_result = $this->raw('SHOW CREATE TABLE `' . $this->escape($table) . '`');

                if (isset($create_query_result[0]['Create View']))
                {
                    $create_query                = $create_query_result[0]['Create View'];
                    $create_query                = (mb_strpos($create_query, 'CREATE ALGORITHM') === 0) ? preg_replace('/DEFINER=`(.*?(?=` ))` /', '', $create_query) : $create_query;
                    $creation_queries['views'][] = $create_query;
                }

                else
                {
                    $create_query                 = $create_query_result[0]['Create Table'];
                    $creation_queries['tables'][] = $create_query;
                }

            }

            /*
             * Switch to the target database
             */
            $db = $this->swap($target_connection);

            /*
             * Drop all tables and views in the target database
             */
            $tables = $db->getTables();

            foreach ($tables as $table)
            {
                $db->drop($table);
                $db->dropView($table);
            }

            /*
             * Recreate tables in target database
             */
            foreach ($creation_queries['tables'] as $creation_query)
            {
                $db->raw($creation_query);
            }

            /*
             * Recreate views in target database
             */
            foreach ($creation_queries['views'] as $creation_query)
            {
                $db->raw($creation_query);
            }

        }

        catch (Exception $exception)
        {
            throw new UnknownDuplicationException($exception->getMessage(), 0, $exception);
        }

    }


    /**
     * Dump the database to file
     * @param  string $filename File path/name to dump to
     * @param  string $path     Path to mysqldump binary
     * @return bool             Whether the dump was successfully created
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if a filename for the dump has not been provided
     * @throws UnwritableFileException if the file directory is not writable
     */
    public function dump($filename = '', $path = '')
    {

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$filename === '')
        {
            throw new InvalidArgumentException('No filename provided for database dump');
        }
        
        if (!is_readable(dirname($filename)) OR
            !is_writable(dirname($filename)))
        {
            throw new UnwritableFileException('Database dump path is not writable');
        }

        if ($path != '')
        {
            $path = DIRECTORY_SEPARATOR . trim(realpath($path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }

        exec($path . 'mysqldump --opt -p' . escapeshellarg($this->_connection_details['port']) . ' -h ' . escapeshellarg($this->_connection_details['host']) . ' -u ' . escapeshellarg($this->_connection_details['username']) . ' --password=' . escapeshellarg($this->_connection_details['password']) . ' ' . escapeshellarg($this->_connection_details['database']) . ' --single-transaction > ' . $filename);

        return file_exists($filename);

    }


    /**
     * Import a dump file into the database
     * @param  string $filename Filename to import from
     * @param  string $path     Path to mysqldump binary
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the dump file cannot be read
     */
    public function import($filename = '', $path = '')
    {

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$filename === '' OR !is_readable($filename))
        {
            throw new InvalidArgumentException('Import file could not be read');
        }

        if ($path != '')
        {
            $path = DIRECTORY_SEPARATOR . trim(realpath($path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }

        exec($path . 'mysql -p' . escapeshellarg($this->_connection_details['port']) . ' -h ' . escapeshellarg($this->_connection_details['host']) . ' -u ' . escapeshellarg($this->_connection_details['username']) . ' --password=' . escapeshellarg($this->_connection_details['password']) . ' ' . escapeshellarg($this->_connection_details['database']) . ' < ' . $filename);

    }


    /**
     * Execute a raw MySQL query
     * @param  string       $query     Query to execute
     * @param  array        $values    Array of placeholder values
     * @param  bool         $use_array Optional argument to return results in an array
     * @return object|array            MySQLi object or associative array of results
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if a query string has not been provided
     * @throws InvalidQueryException if the query could not be executed
     */
    public function raw($query = '', array $values = [], $use_array = true)
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$query === '')
        {
            throw new InvalidArgumentException('Query not provided');
        }

        if (!empty($values))
        {

            $prepared_values = $this->_getPreparedValues($values);
            $values_array    = $prepared_values['values_array'];
            $types           = $prepared_values['values_types'];

            /*
             * Add the data-type list onto the beginning of the values array
             */
            if ($types != '')
            {
                array_unshift($values_array, $types);
            }

        }

        /*
         * Combine the above into a prepared statement
         */
        $statement = $this->_mysqli->prepare($query);

        /*
         * Store the query
         */
        $this->_last_query  = $query;
        $this->_last_values = isset($values_array) ? $values_array : [];

        if (!$statement)
        {
            throw new InvalidQueryException('Invalid query: ' . $this->_mysqli->error);
        }

        /*
         * Build the values array into an array of references and pass it to
         * be bound to the statement
         */
        if (!empty($values))
        {
            call_user_func_array([$statement, 'bind_param'], $this->_buildReferenceArray($values_array));
        }

        /*
         * Execute the prepared statement
         */
        if ($statement->execute())
        {

            /*
             * Store the results in an array
             */
            if ($use_array AND
                (($metadata = mysqli_stmt_result_metadata($statement)) !== false))
            {

                $result = [];

                /*
                 * Bind the results to $row
                 */
                $row = [];

                $this->_bindStatementResultColumns($statement, $row, $metadata);

                $row_count = 0;

                /*
                 * Iterate through the results, using $row as an associative
                 * array
                 */
                while ($statement->fetch())
                {

                    foreach ($row as $var => $value)
                    {
                        $result[$row_count][$var] = $value;
                    }

                    $row_count++;

                }

                mysqli_stmt_close($statement);

                return $result;

            }

            /*
             * Return the MySQLi query result object, if requested
             */
            else
            {
                return $statement;
            }

        }

        else
        {
            throw new InvalidQueryException('Could not execute query: ' . $query);
        }

    }
  

    /**
     * Return the primary key of a table
     * @param  string $the_table_name Table name
     * @return string                 Column name of the table's primary key
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the table name has not been provided
     * @throws InvalidSchemaException if a primary key does not exist for the table
     */
    public function getPrimaryKey($the_table_name = null)
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$the_table_name === '')
        {
            throw new InvalidArgumentException('Table name not provided');
        }

        $result = $this->raw('SHOW KEYS FROM `' . $this->escape($the_table_name) . '` WHERE `Key_name` = \'PRIMARY\'');

        if (isset($result[0]['Column_name']))
        {
            return $result[0]['Column_name'];
        }

        throw new InvalidSchemaException('Primary key does not exist');

    }


    /**
     * Return the key of the first column of a table
     * @param  string $the_table_name Table name
     * @return string                 Key of the table's first column
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the table name has not been provided
     * @throws InvalidSchemaException if the table does not contain any fields
     */
    protected function _getFirstColumnName($the_table_name = null)
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$the_table_name === '')
        {
            throw new InvalidArgumentException('Table name not provided');
        }

        $result = $this->raw('SHOW COLUMNS FROM `' . $this->escape($the_table_name) . '`');

        if (isset($result[0]['Field']))
        {
            return $result[0]['Field'];
        }

        throw new InvalidSchemaException('Database table \'' . $the_table_name . '\' does not have any columns');

    }


    /**
     * Get the auto-increment column name
     * @param  string      $table Table name
     * @return string|bool        Name of auto-increment column (or FALSE if one does not exist)
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the table name has not been provided
     */
    public function getAutoIncrementField($table = null)
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$table === '')
        {
            throw new InvalidArgumentException('Table name not provided');
        }

        $meta = $this->raw('DESCRIBE `' . $this->escape($table) . '`');

        foreach ($meta as $column)
        {

            if (mb_strstr($column['Extra'], 'auto_increment'))
            {
                return $column['Field'];
            }

        }

        return false;

    }


    /**
     * Get a list of all columns in a table
     * @param  string $table Table name
     * @return array         Array of column/field names
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the table name has not been provided
     */
    public function getFields($table = null)
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$table === '')
        {
            throw new InvalidArgumentException('Table name not provided');
        }

        $meta    = $this->raw('DESCRIBE `' . $this->escape($table) . '`');
        $columns = [];

        foreach ($meta as $column)
        {
            $field     = $column['Field'];
            $columns[] = $field;
        }

        return $columns;

    }


    /**
     * Take an array of key/value pairs and return an array of strings and value
     * types to be used in MySQLi prepared statements
     * @param  array  $key_values_array Array of key/value pairs
     * @return array                    Array of strings and value types
     * @throws MissingDependencyException if MySQLi is missing
     */
    protected function _getPreparedValues(array $key_values_array = [])
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        $key_values       = '';
        $value_values     = '';
        $key_value_values = '';
        $values_array     = [];
        $value_types      = '';

        foreach ($key_values_array as $var => $value)
        {

            /*
             * Columns
             */
            $key_values .= ', `' . $this->escape($var) . '`';

            /*
             * Placeholders
             */
            $value_values .= ', ?';

            /*
             * Columns and placeholders
             */
            $key_value_values .= ', `' . $this->escape($var) . '` = ?';

            /*
             * Value
             */
            $values_array[] = $value;

            /*
             * String data type
             */
            if (is_string($value) OR is_null($value))
            {
                $value_types .= 's';
            }

            /*
             * Integer data type
             */
            else if (is_int($value))
            {
                $value_types .= 'i';
            }

            /*
             * Double data type
             */
            else if (is_double($value))
            {
                $value_types .= 'd';
            }

            /*
             * Other data type (assumed BLOB)
             */
            else
            {
                $value_types .= 'b';
            }

        }

        $key_values       = ltrim($key_values, ', ');
        $value_values     = ltrim($value_values, ', ');
        $key_value_values = ltrim($key_value_values, ', ');

        return ['key_values'       => $key_values,
                'value_values'     => $value_values,
                'key_value_values' => $key_value_values,
                'values_array'     => $values_array,
                'values_types'     => $value_types];

    }


    /**
     * Compile a WHERE clause for use with a MySQLi prepared statement
     * @param  array $conditional_array Nested array of conditions
     * @return array                    Array of values, types and WHERE clause
     * @throws MissingDependencyException if MySQLi is missing
     */
    protected function _prepareWhereClause(array $conditional_array = [])
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        $where_clause = '';
        $where_array  = [];
        $value_types  = '';

        foreach ($conditional_array as &$value)
        {

            /*
             * If the value isn't describing a nested condition (standard
             * clause)
             */
            if (!isset($value['sub']))
            {

                /*
                 * Fields equal
                 */
                if (isset($value['compare']) AND strtoupper($value['compare']) == 'WHERE_FIELDS_EQUAL')
                {
                    $where_clause   .= ' ' . (isset($value['type']) ? $this->escape(strtoupper($value['type'])) : 'AND') . ' (\'1\' = ? AND `' . str_replace('.', '`.`', $this->escape($value['field'])) . '` = `' . str_replace('.', '`.`', $this->escape($value['value'])) . '`)';
                    $value['value']  = '1';
                }

                /*
                 * LIKE
                 */
                else if (isset($value['compare']) AND
                    (strtoupper($value['compare']) == 'LIKE' OR strtoupper($value['compare']) == 'NOT LIKE'))
                {
                    $where_clause   .= ' ' . (isset($value['type']) ? $this->escape(strtoupper($value['type'])) : 'AND') . ' (`' . str_replace('.', '`.`', $this->escape($value['field'])) . '` ' . $this->escape(strtoupper($value['compare'])) . ' CONCAT(\'%\', ?, \'%\'))';
                    $value['value']  = $this->likeEscape($value['value']);
                }

                /*
                 * IN
                 */
                else if (isset($value['operator']) AND
                         (strtoupper($value['operator']) == 'IN' OR strtoupper($value['operator']) == 'NOT IN') AND
                         is_array($value['value']))
                {

                    /*
                     * Filter duplicates
                     */
                    $value['value'] = array_unique($value['value']);
                    
                    /*
                     * If an array of values has been passed
                     */
                    if (count($value['value']))
                    {
                        $in_list       = implode(',', array_fill(0, count($value['value']), '?'));
                        $where_clause .= ' ' . (isset($value['type']) ? $this->escape(strtoupper($value['type'])) : 'AND') . ' (`' . str_replace('.', '`.`', $this->escape($value['field'])) . '` ' . $this->escape(strtoupper($value['operator'])) . ' (' . $in_list . '))';
                    }
                    
                    /*
                     * If no values have been passed IN should always fail, so
                     * add WHERE 1 = 2
                     */
                    else if (strtoupper($value['operator']) == 'IN')
                    {
                        $value['value']  = [1, 2];
                        $where_clause   .= ' ' . (isset($value['type']) ? $this->escape(strtoupper($value['type'])) : 'AND') . ' ? = ?';
                    }
                    
                    /*
                     * If no values have been passed NOT IN should always pass,
                     * so add WHERE 1 = 1
                     */
                    else if (strtoupper($value['operator']) == 'NOT IN')
                    {
                        $value['value']  = [1, 1];
                        $where_clause   .= ' ' . (isset($value['type']) ? $this->escape(strtoupper($value['type'])) : 'AND') . ' ? = ?';
                    }
                    
                }

                /*
                 * NULL
                 */
                else if (isset($value['operator']) AND
                         (strtoupper($value['operator']) == 'IS NULL' OR strtoupper($value['operator']) == 'IS NOT NULL'))
                {
                    $where_clause .= ' ' . (isset($value['type']) ? $this->escape(strtoupper($value['type'])) : 'AND') . ' (`' . str_replace('.', '`.`', $this->escape($value['field'])) . '` ' . $this->escape(strtoupper($value['operator'])) . ')';
                }

                /*
                 * Other operator
                 */
                else
                {
                    $where_clause .= ' ' . (isset($value['type']) ? $this->escape(strtoupper($value['type'])) : 'AND') . ' (`' . str_replace('.', '`.`', $this->escape($value['field'])) . '` ' . (isset($value['operator']) ? $this->escape($value['operator']) : '=') . ' ?)';
                }

                /*
                 * If we're not dealing with an array, shove the variable into
                 * one anyway, to save us code
                 */
                $where_value_array = [];
                
                if (!is_array($value['value']))
                {
                    $where_value_array[] = $value['value'];
                }
                
                /*
                 * If it is an array, just copy it straight across
                 */
                else
                {
                    $where_value_array = $value['value'];
                }

                /*
                 * Now iterate through that array and inspect each element
                 */
                foreach ($where_value_array as $value_2)
                {

                    /*
                     * Unless there is no value required for the operator in question
                     */
                    if (!isset($value['operator']) OR
                        isset($value['operator']) AND !in_array(strtoupper($value['operator']), ['IS NULL', 'IS NOT NULL']))
                    {

                        $where_array[] = $value_2;

                        /*
                         * Strings
                         */
                        if (is_string($value_2))
                        {
                            $value_types .= 's';
                        }

                        /*
                         * Integers
                         */
                        else if (is_int($value_2))
                        {
                            $value_types .= 'i';
                        }

                        /*
                         * Doubles
                         */
                        else if (is_double($value_2))
                        {
                            $value_types .= 'd';
                        }

                        /*
                         * BLOBs
                         */
                        else
                        {
                            $value_types .= 'b';
                        }

                    }

                }

            }

            /*
             * If there's a nested array, inspect it
             */
            else
            {
                $temp_recursion  = $this->_prepareWhereClause($value['sub']);
                $where_clause   .= ' ' . (isset($value['type']) ? $this->escape(strtoupper($value['type'])) : 'AND') . ' (' . $temp_recursion['where_clause'] . ')';
                $value_types    .= $temp_recursion['values_types'];
                $where_array     = array_merge($where_array, $temp_recursion['where_array']);
            }

        }

        $where_clause = ltrim(ltrim($where_clause, ' AND '), ' OR ');

        $this->_last_types = $value_types;

        return ['where_clause' => $where_clause,
                'where_array'  => $where_array,
                'values_types' => $value_types];

    }


    /**
     * Build an array of column name references to bind to an executed MySQLi
     * statement
     * @param  object      $the_statement   Reference to an executed MySQLi statement
     * @param  array       $external_target Reference to an array to bind results to
     * @param  object|null $metadata        Optional prepared statement metadata
     * @return bool                         Whether the action was successful
     * @throws MissingDependencyException if MySQLi is missing
     */
    protected function _bindStatementResultColumns(&$the_statement, &$external_target, $metadata = null)
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if ($this->isConnected())
        {

            $temp_data       = is_null($metadata) ? mysqli_stmt_result_metadata($the_statement) : $metadata;
            $temp_fields     = [];
            $external_target = [];
            $temp_fields[0]  = $the_statement;
            $count           = 1;
            
            while ($temp_field = mysqli_fetch_field($temp_data))
            {
                $temp_fields[$count++] = &$external_target[$temp_field->name];
            }
            
            call_user_func_array('mysqli_stmt_bind_result', $temp_fields);

        }

        return true;

    }


    /**
     * Escape a string for use in a query
     * @param  string $string String to escape
     * @return string         Escaped string
     * @throws MissingDependencyException if MySQLi is missing
     */
    public function escape($string = '')
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        return $this->_mysqli->real_escape_string($string);

    }


    /**
     * Escape a string for use in a LIKE query
     * @param  string $string String to escape
     * @return string         Escaped string
     */
    public function likeEscape($string = '')
    {

        return str_replace('%', '\\%', str_replace('_', '\\_', $string));

    }


    /**
     * Parse an individual field array for CREATE and ALTER queries
     * @param  array $field_array Array of field information
     * @return bool               SQL fragment
     */
    protected function _parseCreateField(array $field_array = [])
    {

        $result = '`' . $this->escape($field_array['field']) . '` ' . $this->escape(strtolower($field_array['type']));

        /*
         * Escape 'length' attribute
         */
        if (isset($field_array['length']))
        {

            $length = '';
            
            /*
             * If an array, only escape the contents
             */
            if (is_array($field_array['length']))
            {

                /*
                 * Remove duplicate values
                 */
                $field_array['length'] = array_unique($field_array['length']);

                foreach ($field_array['length'] as $value_2)
                {
                    $length .= '\'' . $this->escape((string)$value_2) . '\',';
                }

                $length = rtrim($length, ',');

            }
            
            /*
             * If not an array, treat as a string and escape it
             */
            else
            {
                $length = $this->escape(trim((string)$field_array['length']));
            }
            
            $result .= '(' . $length . ')';

        }

        /*
         * Null
         */
        if (isset($field_array['null']))
        {
            $result .= $field_array['null'] ? ' NULL' : ' NOT NULL';
        }

        /*
         * Default value
         */
        if (isset($field_array['default']))
        {
            $result .= ' DEFAULT \'' . $this->escape((string)$field_array['default']) . '\'';
        }

        /*
         * Auto-increment
         */
        if (isset($field_array['auto_increment']) AND $field_array['auto_increment'])
        {
            $result .= $field_array['auto_increment'] ? ' AUTO_INCREMENT' : '';
        }

        /*
         * Comment
         */
        if (isset($field_array['comment']))
        {
            $result .= ' COMMENT \'' . $this->escape((string)$field_array['comment']) . '\'';
        }

        return $result;

    }


    /**
     * Create a database table
     * @param  string $the_table     Name of table to create
     * @param  array  $columns_array Associative array of columns to create in new table
     * @param  string $character_set Default character set
     * @param  string $collation     Default collation
     * @return bool                  Whether the table was successfully created
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the table name has not been provided
     * @throws InvalidArgumentException if schema data has not been provided
     * @throws InvalidQueryException if the query could not be executed
     */
    public function create($the_table = null, array $columns_array = [], $character_set = 'utf8mb4', $collation = 'utf8mb4_unicode_ci')
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$the_table === '')
        {
            throw new InvalidArgumentException('Table name not provided');
        }

        if (empty($columns_array))
        {
            throw new InvalidArgumentException('Schema data not provided');
        }

        $temp_result = false;

        $primary_key = '';
        $keys        = [];

        $query = 'CREATE TABLE IF NOT EXISTS `' . $this->escape($the_table) . '` (';

        foreach ($columns_array as $value)
        {

            /*
             * Parse the value and create some of the query in advance
             */
            $query .= $this->_parseCreateField($value) . ', ';

            $column_type = strtolower($value['type']);

            /*
             * 'Text' and 'BLOB' fields can't have keys, as different
             * storage engines treat their requirements slightly differently
             */
            if ($column_type != 'text' AND $column_type != 'blob')
            {

                /*
                 * Is it the primary key?
                 */
                if (isset($value['primary_key']) AND $value['primary_key'])
                {
                    $primary_key = $value['field'];
                }

                /*
                 * Is it a generic key?
                 */
                if (isset($value['key']) AND $value['key'])
                {
                    $keys[] = $value['field'];
                }

            }

        }

        /*
         * Add a primary key if one was defined
         */
        if ($primary_key)
        {
            $query .= ' PRIMARY KEY (`' . $this->escape($primary_key) . '`)';
        }

        $query = rtrim($query, ', ');

        /*
         * Add any generic keys that were defined
         */
        if (count($keys))
        {

            $query .= ',';

            foreach ($keys as $value)
            {
                $query .= ' KEY `key_' . $this->escape($value) . '` (`' . $this->escape($value) . '`),';
            }

        }

        $query  = rtrim($query, ',');
        $query .= ')';

        if ((string)$character_set !== '')
        {
            $query .= ' DEFAULT CHARACTER SET=' . $this->escape($character_set);
        }

        if ((string)$collation !== '')
        {
            $query .= ' DEFAULT COLLATE ' . $this->escape($collation);
        }

        /*
         * Prepare the query
         */
        $temp_statement = $this->_mysqli->prepare($query);

        /*
         * Store the query
         */
        $this->_last_query  = $query;
        $this->_last_values = [];

        if (!$temp_statement)
        {
            throw new InvalidQueryException('Invalid query: ' . $this->_mysqli->error);
        }

        /*
         * Execute the prepared statement
         */
        if ($temp_statement->execute())
        {
            $temp_result = true;
        }

        mysqli_stmt_close($temp_statement);

        if (!$temp_result)
        {
            throw new InvalidQueryException('Could not execute query: ' . $query);
        }

        return $temp_result;

    }


    /**
     * Build a database schema
     * @return SchemaBuilder SchemaBuilder object
     */
    public function buildSchema()
    {

        $schema = new SchemaBuilder($this);

        return $schema;

    }


    /**
     * Add a field to an existing database table
     * @param  string $the_table     Name of table to add field to
     * @param  array  $column_array  Associative array of column details to add
     * @param  string $character_set Character set
     * @param  string $collation     Collation
     * @return bool                  Whether the field was successfully added
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the table name has not been provided
     * @throws InvalidArgumentException if the field schema data has not been provided
     * @throws InvalidQueryException if the query could not be executed
     */
    public function addField($the_table = null, array $column_array = [], $character_set = 'utf8mb4', $collation = 'utf8mb4_unicode_ci')
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$the_table === '')
        {
            throw new InvalidArgumentException('Table name not provided');
        }

        if (empty($column_array))
        {
            throw new InvalidArgumentException('Field schema data not provided');
        }

        $temp_result = false;

        $primary_key = '';
        $keys        = [];
        $query       = 'ALTER TABLE `' . $this->escape($the_table) . '` ADD ';
        $query      .= $this->_parseCreateField($column_array) . ', ';
        $column_type = strtolower($column_array['type']);

        /*
         * 'Text' and 'BLOB' fields can't have keys, as different storage
         * engines treat their requirements slightly differently
         */
        if ($column_type != 'text' AND $column_type != 'blob')
        {

            if (isset($column_array['primary_key']) AND $column_array['primary_key'])
            {
                $primary_key = $column_array['field'];
            }

            if (isset($column_array['key']) AND $column_array['key'])
            {
                $keys[] = $column_array['field'];
            }

        }

        if ((string)$character_set !== '')
        {
            $query  = ltrim($query, ', ');
            $query .= ' CHARACTER SET ' . $this->escape($character_set) . ', ';
        }

        if ((string)$collation !== '')
        {
            $query  = ltrim($query, ', ');
            $query .= ' COLLATE ' . $this->escape($collation) . ', ';
        }

        if ($primary_key)
        {
            $query .= ' ADD PRIMARY KEY (`' . $this->escape($primary_key) . '`)';
        }

        $query = rtrim($query, ', ');

        if (count($keys))
        {

            $query .= ',';

            foreach ($keys as $value)
            {
                $query .= ' ADD KEY `key_' . $this->escape($value) . '` (`' . $this->escape($value) . '`),';
            }

        }

        $query = rtrim($query, ',');

        /*
         * Prepare the query
         */
        $temp_statement = $this->_mysqli->prepare($query);

        /*
         * Store the query
         */
        $this->_last_query  = $query;
        $this->_last_values = [];

        if (!$temp_statement)
        {
            throw new InvalidQueryException('Invalid query: ' . $this->_mysqli->error);
        }

        /*
         * Execute the prepared statement
         */
        if ($temp_statement->execute())
        {
            $temp_result = true;
        }

        mysqli_stmt_close($temp_statement);

        if (!$temp_result)
        {
            throw new InvalidQueryException('Could not execute query: ' . $query);
        }

        $this->_clearCache($the_table);

        return $temp_result;

    }


    /**
     * Alter an existing field in a database table
     * @param  string $the_table     Name of table in which to alter field
     * @param  array  $column_array  Associative array of column details to alter
     * @param  string $character_set Character set
     * @param  string $collation     Collation
     * @return bool                  Whether the field was successfully altered
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the table name has not been provided
     * @throws InvalidArgumentException if schema data has not been provided
     * @throws InvalidQueryException if the query could not be executed
     */
    public function alterField($the_table = null, array $column_array = [], $character_set = 'utf8mb4', $collation = 'utf8mb4_unicode_ci')
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$the_table === '')
        {
            throw new InvalidArgumentException('Table name not provided');
        }

        if (empty($column_array))
        {
            throw new InvalidArgumentException('Schema data not provided');
        }

        $temp_result = false;

        $primary_key  = '';
        $keys         = [];
        $query        = 'ALTER TABLE `' . $this->escape($the_table) . '` MODIFY ';
        $query       .= $this->_parseCreateField($column_array) . ', ';
        $column_type  = strtolower($column_array['type']);

        /*
         * 'Text' and 'BLOB' fields can't have keys, as different storage
         * engines treat their requirements slightly differently
         */
        if ($column_type != 'text' AND $column_type != 'blob')
        {

            if (isset($column_array['primary_key']) AND $column_array['primary_key'])
            {
                $primary_key = $column_array['field'];
            }

            if (isset($column_array['key']) AND $column_array['key'])
            {
                $keys[] = $column_array['field'];
            }

        }

        if ((string)$character_set !== '')
        {
            $query  = ltrim($query, ', ');
            $query .= ' CHARACTER SET ' . $this->escape($character_set) . ', ';
        }

        if ((string)$collation !== '')
        {
            $query  = ltrim($query, ', ');
            $query .= ' COLLATE ' . $this->escape($collation) . ', ';
        }

        if ($primary_key)
        {
            $query .= ' ADD PRIMARY KEY (`' . $this->escape($primary_key) . '`)';
        }

        $query = rtrim($query, ', ');

        if (count($keys))
        {

            $query .= ',';

            foreach ($keys as $value)
            {
                $query .= ' ADD KEY `key_' . $this->escape($value) . '` (`' . $this->escape($value) . '`),';
            }

        }

        $query = rtrim($query, ',');

        /*
         * Prepare the query
         */
        $temp_statement = $this->_mysqli->prepare($query);

        /*
         * Store the query
         */
        $this->_last_query  = $query;
        $this->_last_values = [];

        if (!$temp_statement)
        {
            throw new InvalidQueryException('Invalid query: ' . $this->_mysqli->error);
        }

        /*
         * Execute the prepared statement
         */
        if ($temp_statement->execute())
        {
            $temp_result = true;
        }

        mysqli_stmt_close($temp_statement);

        if (!$temp_result)
        {
            throw new InvalidQueryException('Could not execute query: ' . $query);
        }

        $this->_clearCache($the_table);

        return $temp_result;

    }


    /**
     * Drop a field from a database table
     * @param  string $the_table Table to drop field from
     * @param  string $field     Field to drop
     * @return bool              Whether the field was successfully dropped
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the table name has not been provided
     * @throws InvalidArgumentException if the field name has not been provided
     * @throws InvalidQueryException if the query could not be executed
     */
    public function dropField($the_table = null, $field = '')
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$the_table === '')
        {
            throw new InvalidArgumentException('Table name not provided');
        }

        if ((string)$field === '')
        {
            throw new InvalidArgumentException('Field name not provided');
        }

        $temp_result = false;

        $query          = 'ALTER TABLE `' . $this->escape($the_table) . '` DROP `' . $this->escape($field) . '`';
        $temp_statement = $this->_mysqli->prepare($query);

        /*
         * Store the query
         */
        $this->_last_query  = $query;
        $this->_last_values = [];

        if (!$temp_statement)
        {
            throw new InvalidQueryException('Invalid query: ' . $this->_mysqli->error);
        }

        /*
         * Execute the prepared statement
         */
        if ($temp_statement->execute())
        {
            $temp_result = true;
        }

        mysqli_stmt_close($temp_statement);

        if ($temp_result === false)
        {
            throw new InvalidQueryException('Could not execute query: ' . $query);
        }

        $this->_clearCache($the_table);

        return $temp_result;

    }


    /**
     * Requote selected column strings, where backticks should not go on the
     * outside
     * @param  string $selected_column Column selection string
     * @return string                  Requoted column selection string
     */
    protected function _requote($selected_column = '')
    {

        $temp_selected_column = trim(trim(trim($selected_column), '`'));

        if (stristr($temp_selected_column, '`') !== false OR
            strtolower($temp_selected_column) == 'count(*)' OR
            strtolower($temp_selected_column) == 'rand()')
        {
            return $temp_selected_column;
        }

        return $selected_column;

    }


    /**
     * Execute a SELECT query and return the results as an associative array
     * @param  string $the_table             Table to select results from
     * @param  array  $condition_array       Nested array of conditions
     * @param  array  $options_array         Array of order, group, limit & join options
     * @param  array  $fields_array          Array of columns to return
     * @param  int    $cache_ttl             Time in seconds to cache results for
     * @param  bool   $return_query_for_view Whether to return the query instead of results for use in creating a view
     * @return array                         Associative array of results
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the table name has not been provided
     * @throws InvalidQueryException if the query could not be executed
     */
    public function select($the_table = null, array $condition_array = [], array $options_array = [], array $fields_array = [], $cache_ttl = 0, $return_query_for_view = false)
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$the_table === '')
        {
            throw new InvalidArgumentException('Table name not provided');
        }

        /*
         * Check to see if we have cached results
         */
        $result_key_hash = $this->_db_cache_prefix . $the_table . '_' . hash('sha1', json_encode([$condition_array, $options_array, $fields_array]));

        if (!$return_query_for_view AND ($cached_result = $this->_dependencies->cache->read($result_key_hash)))
        {
            return json_decode($cached_result, true);
        }

        /*
         * Otherwise, clear the stale cached result (if it exists)
         */
        else
        {
            $this->_dependencies->cache->clear($result_key_hash);
        }

        $temp_result = [];

        /*
         * Make a note of what the primary key field is, for any matches
         */
        try
        {
            $temp_primary_key = $this->getPrimaryKey($the_table);
        }

        catch (Exception $exception)
        {
            $temp_primary_key = false;
        }

        /*
         * Compile a list of columns to return
         */
        $return_columns = '*';

        if (!empty($fields_array))
        {

            /*
             * Add the primary key (if one exists) to the beginning of the
             * fields array, unless creating a view
             */
            if ($temp_primary_key AND !$return_query_for_view)
            {
                array_unshift($fields_array, $the_table . '.' . $temp_primary_key);
            }

            /*
             * Remove duplicate values
             */
            $fields_array = array_map('unserialize', array_unique(array_map('serialize', $fields_array)));

            /*
             * Turn the array into a string for the query
             */
            $return_columns = '';

            foreach ($fields_array as $value)
            {

                if (is_array($value))
                {
                    $temp_field_value = reset($value);
                    $temp_field_key   = key($value);
                    $temp_field_name  = $this->_requote('`' . $this->escape($temp_field_key) . '`') . ' AS `' . $this->escape($temp_field_value) . '`';
                }

                else
                {
                    $temp_field_name = $this->_requote('`' . $this->escape($value) . '`');
                }

                $return_columns .= ', ' . $temp_field_name;

            }

            $return_columns = ltrim($return_columns, ', ');

            /*
             * Replace any dots with properly-quoted versions
             */
            $return_columns = str_replace('.', '`.`', $return_columns);

        }

        if (!empty($condition_array))
        {

            /*
             * Prepare the WHERE clause
             */
            $temp_where_values = $this->_prepareWhereClause($condition_array);
            $where_clause      = $temp_where_values['where_clause'];
            $temp_values_array = $temp_where_values['where_array'];
            $values_types      = $temp_where_values['values_types'];

            /*
             * Add the data-type list onto the beginning of the values array
             */
            if ($values_types != '')
            {
                array_unshift($temp_values_array, $values_types);
            }

        }

        $the_condition = '1 = 1';

        if (!empty($condition_array))
        {
            $the_condition = $where_clause;
        }

        /*
         * Add in any optional parts of the statement
         */
        $join     = '';
        $group_by = '';
        $order_by = '';
        $limit    = '';

        if (!empty($options_array))
        {

            /*
             * JOIN
             */
            if (isset($options_array['join']) AND !empty($options_array['join']))
            {

                $last_table = $the_table;

                foreach ($options_array['join'] as $value)
                {
                    $join       .= ' ' . $this->escape(trim(strtoupper($value['type']))) . ' `' . $this->escape($value['table']) . '` ON `' . ($value['with'] != '' ? $this->escape($value['with']) : $this->escape($last_table)) . '`.`' . $this->escape($value['compare']) . '` = `' . $this->escape($value['table']) . '`.`' . $this->escape($value['against']) . '`';
                    $last_table  = $value['table'];
                }

            }

            /*
             * GROUP BY
             */
            if (isset($options_array['group_by']) AND $options_array['group_by'])
            {
                $group_by = ' GROUP BY `' . str_replace('.', '`.`', $this->escape($options_array['group_by'])) . '`';
            }

            /*
             * ORDER BY
             */
            if (isset($options_array['order_by']) AND
                $options_array['order_by'] AND
                is_array($options_array['order_by']) AND
                !empty($options_array['order_by']))
            {

                foreach ($options_array['order_by'] as $value)
                {
                    $order_by .= ', ' . $this->_requote('`' . str_replace('.', '`.`', $this->escape($value['field'])) . '`') . ' ' . $value['direction'];
                }

                $order_by = ' ORDER BY ' . ltrim($order_by, ', ');

            }

            /*
             * LIMIT
             */
            if (isset($options_array['limit']) AND $options_array['limit'])
            {
                $limit = ' LIMIT ' . $options_array['limit'];
            }

        }

        $query          = 'SELECT ' . $return_columns . ' FROM `' . $this->escape($the_table) . '`' . $join . ' WHERE ' . $the_condition . $group_by . $order_by . $limit;
        $temp_statement = $this->_mysqli->prepare($query);

        /*
         * Store the query
         */
        $this->_last_query  = $query;
        $this->_last_values = isset($temp_values_array) ? $temp_values_array : [];

        /*
         * Return the query, if required
         */
        if ($return_query_for_view)
        {
            return $query;
        }

        if (!$temp_statement)
        {
            throw new InvalidQueryException('Invalid query: ' . $this->_mysqli->error);
        }

        /*
         * Build the values array into an array of references and pass it to
         * be bound to the statement
         */
        if (!empty($condition_array) AND !empty($temp_values_array))
        {
            call_user_func_array([$temp_statement, 'bind_param'], $this->_buildReferenceArray($temp_values_array));
        }

        /*
         * Execute the prepared statement
         */
        if ($temp_statement->execute())
        {

            /*
             * Bind the results to $row
             */
            $row = [];

            $this->_bindStatementResultColumns($temp_statement, $row);

            $non_primary_count = 0;

            /*
             * Iterate through the results, using $row as an associative
             * array
             */
            while ($temp_statement->fetch())
            {

                foreach ($row as $var => $value)
                {

                    /*
                     * Only use a primary key as the index if one exists and
                     * there is not a JOIN in the query -- with JOINs it
                     * would be ambiguous which table the ID came from, or
                     * there could be multiple results that try to use the
                     * same value as the ID
                     */
                    $temp_row_id = ((empty($join) AND $temp_primary_key AND isset($row[$temp_primary_key])) ? $row[$temp_primary_key] : $non_primary_count);

                    $temp_result[$temp_row_id][$var] = $value;

                }

                $non_primary_count++;

            }

            mysqli_stmt_close($temp_statement);

        }

        else
        {
            throw new InvalidQueryException('Could not execute query: ' . $query);
        }

        /*
         * Cache the result
         */
        if ((int)$cache_ttl)
        {
            $this->_dependencies->cache->write($result_key_hash,
                                               json_encode($temp_result),
                                               (int)$cache_ttl);
        }

        return $temp_result;

    }


    /**
     * Create a view based upon SELECT criteria
     * @param  string $view_name     Name of view
     * @param  string $the_table     Table to select results from
     * @param  array  $options_array Array of order, group, limit & join options
     * @param  array  $fields_array  Array of columns to return
     * @return bool                  Whether the view was successfully created
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the view name has not been provided
     * @throws InvalidArgumentException if the table name has not been provided
     * @throws InvalidQueryException if the query could not be executed
     */
    public function createView($view_name = '', $the_table = null, array $options_array = [], array $fields_array = [])
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$view_name === '')
        {
            throw new InvalidArgumentException('View name not provided');
        }

        if ((string)$the_table === '')
        {
            throw new InvalidArgumentException('Table name not provided');
        }

        $select_query   = $this->select($the_table, [], $options_array, $fields_array, 0, true);
        $query          = 'CREATE VIEW `' . $this->escape($view_name) . '` AS (' . $select_query . ')';
        $temp_statement = $this->_mysqli->prepare($query);

        if (!$temp_statement)
        {
            throw new InvalidQueryException('Invalid query: ' . $this->_mysqli->error);
        }

        if ($temp_statement->execute())
        {
            mysqli_stmt_close($temp_statement);
        }

        else
        {
            throw new InvalidQueryException('Could not execute query: ' . $query);
        }

        return true;

    }


    /**
     * Insert an array of key/value pairs into a table
     * @param  string $the_table    Table to insert values into
     * @param  array  $values_array Array of key/value pairs
     * @return int                  Insert ID
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the table name has not been provided
     * @throws InvalidQueryException if the query could not be executed
     */
    public function insert($the_table = null, array $values_array = [])
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$the_table === '')
        {
            throw new InvalidArgumentException('Table name not provided');
        }

        $temp_result = false;

        /*
         * Prepare the values
         */
        $temp_prepared_values = $this->_getPreparedValues($values_array);
        $key_values           = $temp_prepared_values['key_values'];
        $value_values         = $temp_prepared_values['value_values'];
        $temp_values_array    = $temp_prepared_values['values_array'];
        $temp_types           = $temp_prepared_values['values_types'];

        /*
         * Add the data-type list onto the beginning of the values array
         */
        if ($temp_types != '')
        {
            array_unshift($temp_values_array, $temp_types);
        }

        /*
         * Combine the above into a prepared statement
         */
        $query          = 'INSERT INTO `' . $this->escape($the_table) . '` (' . $key_values . ') VALUES (' . $value_values . ')';
        $temp_statement = $this->_mysqli->prepare($query);

        /*
         * Store the query
         */
        $this->_last_query  = $query;
        $this->_last_values = isset($temp_values_array) ? $temp_values_array : [];

        if (!$temp_statement)
        {
            throw new InvalidQueryException('Invalid query: ' . $this->_mysqli->error);
        }

        /*
         * Build the values array into an array of references and pass it to
         * be bound to the statement
         */
        if (!empty($temp_values_array))
        {
            call_user_func_array([$temp_statement, 'bind_param'], $this->_buildReferenceArray($temp_values_array));
        }

        /*
         * Execute the prepared statement
         */
        if ($temp_statement->execute())
        {
            $temp_result = $temp_statement->insert_id;
        }

        mysqli_stmt_close($temp_statement);

        if ($temp_result === false)
        {
            throw new InvalidQueryException('Could not execute query: ' . $query);
        }

        $this->_clearCache($the_table);

        return $temp_result;

    }


    /**
     * Update table rows with an array of key/value pairs
     * @param  string $the_table       Table to update
     * @param  array  $values_array    Array of key/value pairs
     * @param  array  $condition_array Nested array of conditions
     * @return int                     Number of affected rows
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the table name has not been provided
     * @throws InvalidArgumentException if values have not been provided
     * @throws InvalidQueryException if the query could not be executed
     */
    public function update($the_table = null, array $values_array = [], array $condition_array = [])
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$the_table === '')
        {
            throw new InvalidArgumentException('Table name not provided');
        }

        if (empty($values_array))
        {
            throw new InvalidArgumentException('Values not provided');
        }

        $temp_result = 0;

        /*
         * Prepare the values
         */
        $temp_prepared_values = $this->_getPreparedValues($values_array);
        $key_value_values     = $temp_prepared_values['key_value_values'];
        $temp_values_array    = $temp_prepared_values['values_array'];
        $temp_types           = $temp_prepared_values['values_types'];

        /*
         * Add the data-type list onto the beginning of the values array
         */
        if ($temp_types != '')
        {
            array_unshift($temp_values_array, $temp_types);
        }

        /*
         * Prepare the WHERE clause
         */
        $temp_where_values = $this->_prepareWhereClause($condition_array);
        $where_clause      = $temp_where_values['where_clause'];
        $where_array       = $temp_where_values['where_array'];
        $values_types      = $temp_where_values['values_types'];

        /*
         * Combine these values in with the value values
         */
        $temp_values_array[0] .= $values_types;
        $temp_values_array     = array_merge($temp_values_array, $where_array);

        $the_condition = '1 = 1';

        if (!empty($condition_array))
        {
            $the_condition = $where_clause;
        }

        /*
         * Combine the above into a prepared statement
         */
        $query          = 'UPDATE `' . $this->escape($the_table) . '` SET ' . $key_value_values . ' WHERE ' . $the_condition;
        $temp_statement = $this->_mysqli->prepare($query);

        /*
         * Store the query
         */
        $this->_last_query  = $query;
        $this->_last_values = isset($temp_values_array) ? $temp_values_array : [];

        if (!$temp_statement)
        {
            throw new InvalidQueryException('Invalid query: ' . $this->_mysqli->error);
        }

        /*
         * Build the values array into an array of references and pass it to
         * be bound to the statement
         */
        call_user_func_array([$temp_statement, 'bind_param'], $this->_buildReferenceArray($temp_values_array));

        /*
         * Execute the prepared statement
         */
        if ($temp_statement->execute())
        {
            $temp_result = $temp_statement->affected_rows;
        }

        mysqli_stmt_close($temp_statement);

        if ($temp_statement === false)
        {
            throw new InvalidQueryException('Could not execute query: ' . $query);
        }

        $this->_clearCache($the_table);

        return $temp_result;

    }


    /**
     * Delete table rows based on an array of conditions
     * @param  string $the_table       Table to delete from
     * @param  array  $condition_array Nested array of conditions
     * @return int                     Number of affected rows
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the table name has not been provided
     * @throws InvalidQueryException if the query could not be executed
     */
    public function delete($the_table = null, array $condition_array = [])
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$the_table === '')
        {
            throw new InvalidArgumentException('Table name not provided');
        }

        $temp_result = 0;

        /*
         * Prepare the WHERE clause
         */
        $temp_where_values = $this->_prepareWhereClause($condition_array);
        $where_clause      = $temp_where_values['where_clause'];
        $temp_values_array = $temp_where_values['where_array'];
        $values_types      = $temp_where_values['values_types'];

        /*
         * Add the data-type list onto the beginning of the values array
         */
        if ($values_types != '')
        {
            array_unshift($temp_values_array, $values_types);
        }

        $the_condition = '1 = 1';

        if (!empty($condition_array))
        {
            $the_condition = $where_clause;
        }

        /*
         * Combine the above into a prepared statement
         */
        $query          = 'DELETE FROM `' . $this->escape($the_table) . '` WHERE ' . $the_condition;
        $temp_statement = $this->_mysqli->prepare($query);

        /*
         * Store the query
         */
        $this->_last_query  = $query;
        $this->_last_values = isset($temp_values_array) ? $temp_values_array : [];

        if (!$temp_statement)
        {
            throw new InvalidQueryException('Invalid query: ' . $this->_mysqli->error);
        }

        /*
         * Build the values array into an array of references and pass it to
         * be bound to the statement
         */
        if (!empty($temp_values_array))
        {
            call_user_func_array([$temp_statement, 'bind_param'], $this->_buildReferenceArray($temp_values_array));
        }

        /*
         * Execute the prepared statement
         */
        if ($temp_statement->execute())
        {
            $temp_result = $temp_statement->affected_rows;
        }

        mysqli_stmt_close($temp_statement);

        if ($temp_statement === false)
        {
            throw new InvalidQueryException('Could not execute query: ' . $query);
        }

        $this->_clearCache($the_table);

        return $temp_result;

    }


    /**
     * Count the number of records in a table
     * @param  string $table Table name
     * @return int           Number of records in table
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the table name has not been provided
     */
    protected function _countWholeTable($table = '')
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$table === '')
        {
            throw new InvalidArgumentException('Table name not provided');
        }

        $count = $this->select($table, [], [], [['COUNT(*)' => 'count']]);
        $count = reset($count);

        if (isset($count['count']))
        {
            return $count['count'];
        }

        return null;

    }


    /**
     * Insert record(s) into a table if it is empty
     * @param  string $the_table Table to check
     * @param  array  $the_data  Array of key/value pairs to insert (or array of such arrays)
     * @return bool              Whether or not the new row(s) were created
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the table name has not been provided
     * @throws InvalidArgumentException if values have not been provided
     */
    public function autoPopulate($the_table = null, array $the_data = [])
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$the_table === '')
        {
            throw new InvalidArgumentException('Table name not provided');
        }

        if (empty($the_data))
        {
            throw new InvalidArgumentException('Values not provided');
        }

        $result = true;

        /*
         * See if the table is empty
         */
        if ($this->_countWholeTable($the_table) === 0)
        {

            if (!is_array(reset($the_data)))
            {
                /*
                 * If the data is a single item, turn it into an array
                 */
                $the_data = [$the_data];
            }

            /*
             * Iterate through the array and attempt to add each record
             */
            foreach ($the_data as $value)
            {

                try
                {
                    $this->insert($the_table, $value);
                }

                catch (Exception $exception)
                {
                    $result = false;
                }

            }

        }

        /*
         * If the table is not empty, we will return FALSE
         */
        else
        {
            $result = false;
        }

        return $result;

    }


    /**
     * Fetch a specific value from the first row of results given one field to
     * match by
     * @param  string $the_table  Table to query
     * @param  string $to_give    Field of known value
     * @param  string $give_value Known value
     * @param  string $to_get     Field of target value
     * @param  string $direction  Direction (either ASC or DESC)
     * @param  int    $cache_ttl  Time in seconds to cache results for
     * @return string             First $to_get field
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the table name has not been provided
     * @throws InvalidArgumentException if the lookup field name has not been provided
     * @throws InvalidArgumentException if the target field name has not been provided
     */
    public function getBy($the_table = null, $to_give = null, $give_value = null, $to_get = null, $direction = 'ASC', $cache_ttl = 0)
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$the_table === '')
        {
            throw new InvalidArgumentException('Table name not provided');
        }

        if ((string)$to_give === '')
        {
            throw new InvalidArgumentException('Lookup field name not provided');
        }

        if ((string)$to_get === '')
        {
            throw new InvalidArgumentException('Target field name not provided');
        }

        $direction = (strtoupper($direction) == 'ASC') ? 'ASC' : 'DESC';

        /*
         * Utilise the select() method to return the data
         */
        $condition_array = [['field' => $to_give,
                             'type'  => 'AND',
                             'value' => $give_value]];

        /*
         * Only retrieve the first matching record
         */
        $options_array = ['limit' => 1];

        /*
         * Set the order (use the first column if no primary key exists)
         */
        try
        {
            $temp_primary_key = $this->getPrimaryKey($the_table);
        }

        /*
         * If no primary key, use the first column
         */
        catch (Exception $exception)
        {
            $temp_primary_key = $this->_getFirstColumnName($the_table);
        }

        $options_array['order_by'] = [['field'     => $temp_primary_key,
                                       'direction' => $direction]];

        $temp_result = $this->select($the_table, $condition_array, $options_array, [$to_get], (int)$cache_ttl);

        foreach ($temp_result as $value)
        {

            if (isset($value[$to_get]))
            {
                return $value[$to_get];
            }

        }

        return null;

    }


    /**
     * Fetch a specific value from the first row of results given a set of
     * conditions
     * @param  string $the_table       Table to query
     * @param  array  $condition_array Nested array of conditions
     * @param  string $to_get          Field of target value
     * @param  string $direction       Direction (either ASC or DESC)
     * @param  int    $cache_ttl       Time in seconds to cache results for
     * @return string                  First $to_get field
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the table name has not been provided
     * @throws InvalidArgumentException if the lookup conditions have not been provided
     * @throws InvalidArgumentException if the target field name has not been provided
     */
    public function getByComplex($the_table = null, array $condition_array = [], $to_get = null, $direction = 'ASC', $cache_ttl = 0)
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$the_table === '')
        {
            throw new InvalidArgumentException('Table name not provided');
        }

        if (empty($condition_array))
        {
            throw new InvalidArgumentException('Lookup conditions not provided');
        }

        if ((string)$to_get === '')
        {
            throw new InvalidArgumentException('Target field name not provided');
        }

        $direction = (strtoupper($direction) == 'ASC') ? 'ASC' : 'DESC';

        /*
         * Only retrieve the first matching record
         */
        $options_array = ['limit' => 1];

        /*
         * Set the order (use the first column if no primary key exists)
         */
        try
        {
            $temp_primary_key = $this->getPrimaryKey($the_table);
        }

        /*
         * If no primary key, use the first column
         */
        catch (Exception $exception)
        {
            $temp_primary_key = $this->_getFirstColumnName($the_table);
        }

        $options_array['order_by'] = [['field'     => $temp_primary_key,
                                       'direction' => $direction]];

        /*
         * Perform the query
         */
        $temp_result = $this->select($the_table, $condition_array, $options_array, [$to_get], (int)$cache_ttl);

        foreach ($temp_result as $value)
        {

            if ($value[$to_get])
            {
                return $value[$to_get];
            }

        }

        return null;

    }


    /**
     * Drop a database table
     * @param  string $the_table Table to drop
     * @return bool              Whether the table was successfully dropped
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the table name has not been provided
     * @throws InvalidQueryException if the query could not be executed
     */
    public function drop($the_table = null)
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$the_table === '')
        {
            throw new InvalidArgumentException('Table name not provided');
        }

        $temp_result = false;

        /*
         * Prepare the query
         */
        $query          = 'DROP TABLE IF EXISTS `' . $this->escape($the_table) . '`';
        $temp_statement = $this->_mysqli->prepare($query);

        /*
         * Store the query
         */
        $this->_last_query  = $query;
        $this->_last_values = [];

        if (!$temp_statement)
        {
            throw new InvalidQueryException('Invalid query: ' . $this->_mysqli->error);
        }

        /*
         * Execute the prepared statement
         */
        if ($temp_statement->execute())
        {
            $temp_result = true;
        }

        mysqli_stmt_close($temp_statement);

        if ($temp_result === false)
        {
            throw new InvalidQueryException('Could not execute query: ' . $query);
        }

        $this->_clearCache($the_table);

        return $temp_result;

    }


    /**
     * Drop a database view
     * @param  string $view_name Name of view to drop
     * @return bool              Whether the view was successfully dropped
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the view name has not been provided
     * @throws InvalidQueryException if the query could not be executed
     */
    public function dropView($view_name = null)
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$view_name === '')
        {
            throw new InvalidArgumentException('View name not provided');
        }

        $temp_result = false;

        /*
         * Prepare the query
         */
        $query          = 'DROP VIEW IF EXISTS `' . $this->escape($view_name) . '`';
        $temp_statement = $this->_mysqli->prepare($query);

        /*
         * Store the query
         */
        $this->_last_query  = $query;
        $this->_last_values = [];

        if (!$temp_statement)
        {
            throw new InvalidQueryException('Invalid query: ' . $this->_mysqli->error);
        }

        /*
         * Execute the prepared statement
         */
        if ($temp_statement->execute())
        {
            $temp_result = true;
        }

        mysqli_stmt_close($temp_statement);

        if ($temp_result === false)
        {
            throw new InvalidQueryException('Could not execute query: ' . $query);
        }

        $this->_clearCache($view_name);

        return $temp_result;

    }


    /**
     * Truncate a database table
     * @param  string $the_table Table to truncate
     * @return bool              Whether the table was successfully truncated
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the table name has not been provided
     * @throws InvalidQueryException if the query could not be executed
     */
    public function truncate($the_table = null)
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$the_table === '')
        {
            throw new InvalidArgumentException('Table name not provided');
        }

        $temp_result = false;

        /*
         * Prepare the query
         */
        $query          = 'TRUNCATE TABLE `' . $this->escape($the_table) . '`';
        $temp_statement = $this->_mysqli->prepare($query);

        /*
         * Store the query
         */
        $this->_last_query  = $query;
        $this->_last_values = [];

        if (!$temp_statement)
        {
            throw new InvalidQueryException('Invalid query: ' . $this->_mysqli->error);
        }

        /*
         * Execute the prepared statement
         */
        if ($temp_statement->execute())
        {
            $temp_result = true;
        }

        mysqli_stmt_close($temp_statement);

        if ($temp_result === false)
        {
            throw new InvalidQueryException('Could not execute query: ' . $query);
        }

        $this->_clearCache($the_table);

        return $temp_result;

    }


    /**
     * Rename a database table
     * @param  string $the_table Table to rename
     * @param  string $new_name  New name for table
     * @return bool              Whether the table was successfully renamed
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the current table name has not been provided
     * @throws InvalidArgumentException if the new table name has not been provided
     * @throws InvalidQueryException if the query could not be executed
     */
    public function rename($the_table = null, $new_name = '')
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!$this->isConnected())
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        if ((string)$the_table === '')
        {
            throw new InvalidArgumentException('Current table name not provided');
        }

        if ((string)$new_name === '')
        {
            throw new InvalidArgumentException('New table name not provided');
        }

        $temp_result = false;

        /*
         * Prepare the query
         */
        $query          = 'RENAME TABLE `' . $this->escape($the_table) . '` TO `' . $this->escape($new_name) . '`';
        $temp_statement = $this->_mysqli->prepare($query);

        /*
         * Store the query
         */
        $this->_last_query  = $query;
        $this->_last_values = [];

        if (!$temp_statement)
        {
            throw new InvalidQueryException('Invalid query: ' . $this->_mysqli->error);
        }

        /*
         * Execute the prepared statement
         */
        if ($temp_statement->execute())
        {
            $temp_result = true;
        }

        mysqli_stmt_close($temp_statement);

        if ($temp_result === false)
        {
            throw new InvalidQueryException('Could not execute query: ' . $query);
        }

        $this->_clearCache($the_table);
        $this->_clearCache($new_name);

        return $temp_result;

    }


    /**
     * Clear the cache for a particular table
     * @param string $the_table Name of table from which to clear cached results
     */
    protected function _clearCache($the_table = '')
    {

        $key_hash_fragment = $this->_db_cache_prefix . $the_table . '_';

        $this->_dependencies->cache->prefixClear($key_hash_fragment);

    }
  

    /**
     * Enable or disable MySQLi autocommit
     * @param  bool $the_state Boolean value for desired state of autocommit
     * @return bool            Whether the action was successful
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     */
    public function toggleAutoCommit($the_state = true)
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if ($this->isConnected())
        {
            return mysqli_autocommit($this->_mysqli, !!$the_state);
        }

        throw new InvalidConnectionException('Invalid connection');

    }
  

    /**
     * Manually commit or roll back queries since autocommit was disabled
     * @param  bool $state Whether or not to commit queries
     * @return bool        Whether the action was successful
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     */
    public function finalise($state = true)
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if ($this->isConnected())
        {

            if ($state)
            {
                mysqli_commit($this->_mysqli);
            }

            else
            {
                mysqli_rollback($this->_mysqli);
            }

            /*
             * After the statement has been committed or rolled back, re-set
             * autocommit to true
             */
            $this->toggleAutoCommit(true);

        }

        else
        {
            throw new InvalidConnectionException('Invalid connection');
        }

        return true;

    }


    /**
     * Manually commit or roll back queries since autocommit was disabled, on
     * all database connections
     * @param  bool $state Whether or not to commit queries
     * @return bool        Whether the action was successful
     * @throws MissingDependencyException if MySQLi is missing or the connection is not valid
     */
    public function finaliseAll($state = true)
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        foreach ($this->_connections as $connection_name => $connection)
        {
            $this->swap($connection_name)->finalise($state);
        }

        return true;

    }


    /**
     * Disable autocommit and attempt a transaction
     * @param  Closure $closure Closure containing transaction database calls
     * @return bool             Whether the transaction completed successfully
     * @throws MissingDependencyException if MySQLi is missing or the connection is not valid
     * @throws InvalidArgumentException if a valid closure was not provided
     */
    public function transaction(Closure $closure)
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        if (!(is_object($closure) AND ($closure instanceof Closure)))
        {
            throw new InvalidArgumentException('Invalid closure passed');
        }

        /*
         * Disable autocommit
         */
        $this->toggleAutoCommit(false);

        /*
         * Attempt to execute the queries
         */
        try
        {
            $result = $closure();
        }

        /*
         * Upon unexpected failure, roll back and return FALSE
         */
        catch (Exception $exception)
        {

            $this->finalise(false);

            return false;

        }

        /*
         * If queries executed but the closure returned FALSE, roll back and
         * return FALSE
         */
        if ($result === false)
        {

            $this->finalise(false);

            return false;

        }

        /*
         * In all other circumstances, finalise the transaction and return TRUE
         */
        $this->finalise(true);

        return true;

    }
  

    /**
     * Return the number of rows affected by the last query
     * @return int Number of affected rows
     * @throws MissingDependencyException if MySQLi is missing
     */
    public function affectedRows()
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        return $this->_mysqli->affected_rows;

    }
  

    /**
     * Return the last insert ID
     * @return int Insert ID
     * @throws MissingDependencyException if MySQLi is missing
     */
    public function insertId()
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }
        
        return $this->_mysqli->insert_id;

    }
  

    /**
     * Return the last MySQLi error encountered by the script
     * @return string MySQLi error string
     * @throws MissingDependencyException if the MySQLi extension is missing
     * @throws InvalidConnectionException if the connection is not valid
     */
    public function error()
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }
        
        if (isset($this->_mysqli->error))
        {
            return $this->_mysqli->error;
        }

        throw new InvalidConnectionException('Invalid connection');

    }


    /**
     * Return the last MySQL query that was attempted
     * @return string MySQL query string
     */
    public function lastQuerySql()
    {

        return $this->_last_query;
    
    }


    /**
     * Return any parameterised variables from the last MySQL query that was attempted
     * @return array Array of parameterised variables
     */
    public function lastQueryValues()
    {

        $result = $this->_last_values;

        /*
         * Remove the data type entry at the beginning of the array
         */
        array_shift($result);

        return $result;

    }


    /**
     * Return the variable types of the last MySQL query that was attempted
     * @return string String of variable types
     */
    public function lastQueryTypes()
    {

        return $this->_last_types;

    }


    /**
     * Flush the entire database cache
     */
    public function flushCache()
    {

        $this->_dependencies->cache->prefixClear($this->_db_cache_prefix);

    }

  
    /**
     * Return a chainable object to build custom queries
     * @return QueryBuilder QueryBuilder object for chaining
     * @throws MissingDependencyException if MySQLi is missing
     */
    public function build()
    {

        if (!extension_loaded('mysqli'))
        {
            throw new MissingDependencyException('MySQLi extension missing');
        }

        $builder = new QueryBuilder($this, $this->_dependencies->paginate);

        return $builder;

    }


    /**
     * Take an array of values and return an array of references to them
     * @param  array $array Array of values
     * @return array        Array of value references
     */
    protected function _buildReferenceArray(array $array = [])
    {

        $references = [];

        foreach ($array as $key => $value)
        {
            $references[$key] = &$array[$key];
        }

        return $references;

    }


}