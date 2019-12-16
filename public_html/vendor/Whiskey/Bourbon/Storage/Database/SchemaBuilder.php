<?php


namespace Whiskey\Bourbon\Storage\Database;


use InvalidArgumentException;
use Whiskey\Bourbon\Storage\Database\Mysql\Handler as Database;


/**
 * SchemaBuilder class
 * @package Whiskey\Bourbon\Storage\Database
 */
class SchemaBuilder
{


    const _NO_REUSE = true;


    protected $_dependencies = null;
    protected $_schema       = [];
    protected $_table        = null;


    /**
     * Instantiate the schema builder object
     * @param Database $db Database object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Database $db)
    {

        if (!isset($db))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies = new \stdClass();
        $this->_dependencies->db = $db;

    }


    /**
     * Set the table name
     * @param  string        $name Table name
     * @return SchemaBuilder       SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a name has not been provided
     */
    public function table($name = '')
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to create table');
        }

        $this->_table = $name;

        return $this;

    }


    /**
     * Add an auto-incrementing primary key field
     * @param  string        $name Field name
     * @return SchemaBuilder       SchemaBuilder object for chaining
     */
    public function autoId($name = '')
    {

        if ($name == '')
        {
            $name = 'id';
        }

        $this->_schema[] = ['field'          => $name,
                            'type'           => 'bigint',
                            'length'         => 20,
                            'auto_increment' => true,
                            'primary_key'    => true];

        return $this;

    }


    /**
     * Add a tinyint field
     * @param  string        $name     Field name
     * @param  int           $default  Default value
     * @param  bool          $nullable Whether the field is nullable
     * @return SchemaBuilder           SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a name has not been provided
     */
    public function tinyInt($name = '', $default = null, $nullable = false)
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to create field');
        }

        $field = ['field'  => $name,
                  'type'   => 'tinyint',
                  'length' => 4,
                  'null'   => !!$nullable];

        if (!is_null($default) OR
            (is_null($default) AND $nullable))
        {
            $field['default'] = $default;
        }

        $this->_schema[] = $field;

        return $this;

    }


    /**
     * Add a smallint field
     * @param  string        $name     Field name
     * @param  int           $default  Default value
     * @param  bool          $nullable Whether the field is nullable
     * @return SchemaBuilder           SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a name has not been provided
     */
    public function smallInt($name = '', $default = null, $nullable = false)
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to create field');
        }

        $field = ['field'  => $name,
                  'type'   => 'smallint',
                  'length' => 6,
                  'null'   => !!$nullable];

        if (!is_null($default) OR
            (is_null($default) AND $nullable))
        {
            $field['default'] = $default;
        }

        $this->_schema[] = $field;

        return $this;

    }


    /**
     * Add an int field
     * @param  string        $name     Field name
     * @param  int           $default  Default value
     * @param  bool          $nullable Whether the field is nullable
     * @return SchemaBuilder           SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a name has not been provided
     */
    public function int($name = '', $default = null, $nullable = false)
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to create field');
        }

        $field = ['field'  => $name,
                  'type'   => 'int',
                  'length' => 11,
                  'null'   => !!$nullable];

        if (!is_null($default) OR
            (is_null($default) AND $nullable))
        {
            $field['default'] = $default;
        }

        $this->_schema[] = $field;

        return $this;

    }


    /**
     * Add a bigint field
     * @param  string        $name     Field name
     * @param  int           $default  Default value
     * @param  bool          $nullable Whether the field is nullable
     * @return SchemaBuilder           SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a name has not been provided
     */
    public function bigInt($name = '', $default = null, $nullable = false)
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to create field');
        }

        $field = ['field'  => $name,
                  'type'   => 'bigint',
                  'length' => 20,
                  'null'   => !!$nullable];

        if (!is_null($default) OR
            (is_null($default) AND $nullable))
        {
            $field['default'] = $default;
        }

        $this->_schema[] = $field;

        return $this;

    }


    /**
     * Add a varchar field
     * @param  string        $name     Field name
     * @param  string        $default  Default value
     * @param  bool          $nullable Whether the field is nullable
     * @return SchemaBuilder           SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a name has not been provided
     */
    public function varChar($name = '', $default = null, $nullable = false)
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to create field');
        }

        $field = ['field'  => $name,
                  'type'   => 'varchar',
                  'length' => 2048,
                  'null'   => !!$nullable];

        if (!is_null($default) OR
            (is_null($default) AND $nullable))
        {
            $field['default'] = $default;
        }

        $this->_schema[] = $field;

        return $this;

    }


    /**
     * Add a tinytext field
     * @param  string        $name     Field name
     * @param  bool          $nullable Whether the field is nullable
     * @return SchemaBuilder           SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a name has not been provided
     */
    public function tinyText($name = '', $nullable = false)
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to create field');
        }

        $field = ['field' => $name,
                  'type'  => 'tinytext',
                  'null'  => !!$nullable];

        $this->_schema[] = $field;

        return $this;

    }


    /**
     * Add a text field
     * @param  string        $name     Field name
     * @param  bool          $nullable Whether the field is nullable
     * @return SchemaBuilder           SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a name has not been provided
     */
    public function text($name = '', $nullable = false)
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to create field');
        }

        $field = ['field' => $name,
                  'type'  => 'text',
                  'null'  => !!$nullable];

        $this->_schema[] = $field;

        return $this;

    }


    /**
     * Add a mediumtext field
     * @param  string        $name     Field name
     * @param  bool          $nullable Whether the field is nullable
     * @return SchemaBuilder           SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a name has not been provided
     */
    public function mediumText($name = '', $nullable = false)
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to create field');
        }

        $field = ['field' => $name,
                  'type'  => 'mediumtext',
                  'null'  => !!$nullable];

        $this->_schema[] = $field;

        return $this;

    }


    /**
     * Add a longtext field
     * @param  string        $name     Field name
     * @param  bool          $nullable Whether the field is nullable
     * @return SchemaBuilder           SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a name has not been provided
     */
    public function longText($name = '', $nullable = false)
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to create field');
        }

        $field = ['field' => $name,
                  'type'  => 'longtext',
                  'null'  => !!$nullable];

        $this->_schema[] = $field;

        return $this;

    }


    /**
     * Add a tinyblob field
     * @param  string        $name     Field name
     * @param  bool          $nullable Whether the field is nullable
     * @return SchemaBuilder           SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a name has not been provided
     */
    public function tinyBlob($name = '', $nullable = false)
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to create field');
        }

        $field = ['field' => $name,
                  'type'  => 'tinyblob',
                  'null'  => !!$nullable];

        $this->_schema[] = $field;

        return $this;

    }


    /**
     * Add a blob field
     * @param  string        $name     Field name
     * @param  bool          $nullable Whether the field is nullable
     * @return SchemaBuilder           SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a name has not been provided
     */
    public function blob($name = '', $nullable = false)
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to create field');
        }

        $field = ['field' => $name,
                  'type'  => 'blob',
                  'null'  => !!$nullable];

        $this->_schema[] = $field;

        return $this;

    }


    /**
     * Add a mediumblob field
     * @param  string        $name     Field name
     * @param  bool          $nullable Whether the field is nullable
     * @return SchemaBuilder           SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a name has not been provided
     */
    public function mediumBlob($name = '', $nullable = false)
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to create field');
        }

        $field = ['field' => $name,
                  'type'  => 'mediumblob',
                  'null'  => !!$nullable];

        $this->_schema[] = $field;

        return $this;

    }


    /**
     * Add a longblob field
     * @param  string        $name     Field name
     * @param  bool          $nullable Whether the field is nullable
     * @return SchemaBuilder           SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a name has not been provided
     */
    public function longBlob($name = '', $nullable = false)
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to create field');
        }

        $field = ['field' => $name,
                  'type'  => 'longblob',
                  'null'  => !!$nullable];

        $this->_schema[] = $field;

        return $this;

    }


    /**
     * Add an enum field
     * @param  string        $name     Field name
     * @param  array         $options  Array of options
     * @param  string        $default  Default value
     * @param  bool          $nullable Whether the field is nullable
     * @return SchemaBuilder           SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a name or array of options have not been provided
     */
    public function enum($name = '', array $options = [], $default = null, $nullable = false)
    {

        if ($name == '' OR
            empty($options))
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to create field');
        }

        $field = ['field'  => $name,
                  'type'   => 'enum',
                  'null'   => !!$nullable,
                  'length' => $options];

        if (!is_null($default) OR
            (is_null($default) AND $nullable))
        {
            $field['default'] = $default;
        }

        $this->_schema[] = $field;

        return $this;
        
    }


    /**
     * Add a date field
     * @param  string        $name     Field name
     * @param  string        $default  Default value
     * @param  bool          $nullable Whether the field is nullable
     * @return SchemaBuilder           SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a name has not been provided
     */
    public function date($name = '', $default = null, $nullable = false)
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to create field');
        }

        $field = ['field' => $name,
                  'type'  => 'date',
                  'null'  => !!$nullable];

        if (!is_null($default) OR
            (is_null($default) AND $nullable))
        {
            $field['default'] = $default;
        }

        $this->_schema[] = $field;

        return $this;
        
    }


    /**
     * Add a time field
     * @param  string        $name     Field name
     * @param  string        $default  Default value
     * @param  bool          $nullable Whether the field is nullable
     * @return SchemaBuilder           SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a name has not been provided
     */
    public function time($name = '', $default = null, $nullable = false)
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to create field');
        }

        $field = ['field' => $name,
                  'type'  => 'time',
                  'null'  => !!$nullable];

        if (!is_null($default) OR
            (is_null($default) AND $nullable))
        {
            $field['default'] = $default;
        }

        $this->_schema[] = $field;

        return $this;
        
    }


    /**
     * Add a datetime field
     * @param  string        $name     Field name
     * @param  string        $default  Default value
     * @param  bool          $nullable Whether the field is nullable
     * @return SchemaBuilder           SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a name has not been provided
     */
    public function dateTime($name = '', $default = null, $nullable = false)
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to create field');
        }

        $field = ['field' => $name,
                  'type'  => 'datetime',
                  'null'  => !!$nullable];

        if (!is_null($default) OR
            (is_null($default) AND $nullable))
        {
            $field['default'] = $default;
        }

        $this->_schema[] = $field;

        return $this;
        
    }


    /**
     * Add an auto-updating timestamp field
     * @param  string        $name Field name
     * @return SchemaBuilder       SchemaBuilder object for chaining
     */
    public function timestamp($name = '')
    {

        if ($name == '')
        {
            $name = 'modified';
        }

        $this->_schema[] = ['field' => $name,
                            'type'  => 'timestamp'];

        return $this;

    }


    /**
     * Add a decimal field
     * @param  string        $name      Field name
     * @param  string        $precision Decimal precision
     * @param  float         $default   Default value
     * @param  bool          $nullable  Whether the field is nullable
     * @return SchemaBuilder            SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a name has not been provided
     */
    public function decimal($name = '', $precision = '10,2', $default = null, $nullable = false)
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to create field');
        }

        $field = ['field' => $name,
                  'type'  => 'decimal',
                  'length' => $precision,
                  'null' => !!$nullable];

        if (!is_null($default) OR
            (is_null($default) AND $nullable))
        {
            $field['default'] = $default;
        }

        $this->_schema[] = $field;

        return $this;
        
    }


    /**
     * Add a double field
     * @param  string        $name      Field name
     * @param  string        $precision Double precision
     * @param  float         $default   Default value
     * @param  bool          $nullable  Whether the field is nullable
     * @return SchemaBuilder            SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a name has not been provided
     */
    public function double($name = '', $precision = '', $default = null, $nullable = false)
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to create field');
        }

        $field = ['field' => $name,
                  'type'  => 'double',
                  'null'  => !!$nullable];

        if (!is_null($default) OR
            (is_null($default) AND $nullable))
        {
            $field['default'] = $default;
        }

        if ($precision != '')
        {
            $field['length'] = $precision;
        }

        $this->_schema[] = $field;

        return $this;

    }


    /**
     * Add a float field
     * @param  string        $name      Field name
     * @param  string        $precision Float precision
     * @param  float         $default   Default value
     * @param  bool          $nullable  Whether the field is nullable
     * @return SchemaBuilder            SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a name has not been provided
     */
    public function float($name = '', $precision = '', $default = null, $nullable = false)
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to create field');
        }

        $field = ['field' => $name,
                  'type'  => 'float',
                  'null'  => !!$nullable];

        if (!is_null($default) OR
            (is_null($default) AND $nullable))
        {
            $field['default'] = $default;
        }

        if ($precision != '')
        {
            $field['length'] = $precision;
        }

        $this->_schema[] = $field;

        return $this;

    }


    /**
     * Set the table's primary key
     * @param  string        $name Name of primary key field
     * @return SchemaBuilder       SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a field name has not been provided
     * @throws InvalidArgumentException if the field has not yet been defined
     */
    public function setPrimaryKey($name = '')
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to define primary key');
        }

        $found_field = false;

        foreach ($this->_schema as &$column)
        {

            if ($column['field'] == $name)
            {
                $column['primary_key'] = true;
                $found_field           = true;
            }

        }

        if (!$found_field)
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to define primary key');
        }

        return $this;

    }


    /**
     * Set a key on the table
     * @param  string        $name Name of field
     * @return SchemaBuilder       SchemaBuilder object for chaining
     * @throws InvalidArgumentException if a field name has not been provided
     * @throws InvalidArgumentException if the field has not yet been defined
     */
    public function setKey($name = '')
    {

        if ($name == '')
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to define key');
        }

        $found_field = false;

        foreach ($this->_schema as &$column)
        {

            if ($column['field'] == $name)
            {
                $column['key'] = true;
                $found_field   = true;
            }

        }

        if (!$found_field)
        {
            throw new InvalidArgumentException('Insufficient details provided when attempting to define key');
        }

        return $this;

    }


    /**
     * Create the table
     * @return bool Whether the table was successfully created
     * @throws InvalidArgumentException if insufficient details have been provided
     */
    public function create()
    {

        if (empty($this->_schema) OR
            is_null($this->_table))
        {
            throw new InvalidArgumentException('Insufficient details provided to create a table');
        }

        return $this->_dependencies->db->create($this->_table, $this->_schema);

    }


}