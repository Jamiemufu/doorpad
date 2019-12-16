<?php


namespace Whiskey\Bourbon\Storage\Database\Orm;


use stdClass;
use Closure;
use Exception;
use InvalidArgumentException;
use Whiskey\Bourbon\Exception\EngineNotInitialisedException;
use Whiskey\Bourbon\Exception\Storage\Database\RecordNotFoundException;
use Whiskey\Bourbon\Instance;
use Whiskey\Bourbon\Storage\Database\Mysql\Handler as Database;
use Whiskey\Bourbon\Validation\Handler as Validator;


/**
 * Orm Template class
 * @package Whiskey\Bourbon\Storage\Database\Orm
 */
abstract class Template
{


    const _NO_REUSE = true;


    protected $_bourbon_orm_object_dependencies      = null;
    protected $_bourbon_orm_object_table             = null;
    protected $_bourbon_orm_object_primary_key       = null;
    protected $_bourbon_orm_object_primary_key_value = null;
    protected $_bourbon_orm_object_record_details    = [];
    protected $_bourbon_orm_object_properties        = [];
    protected $_bourbon_orm_object_relationships     = [];
    protected $_bourbon_orm_object_belongs_to        = [];
    protected $_bourbon_orm_object_has               = [];
    protected $_bourbon_orm_object_blacklist         = [];
    protected $_bourbon_orm_object_validators        = [];


    protected static $_bourbon_orm_stored_instances = [];


    /**
     * Set a database table for the ORM object
     * @param string      $table         Database table name
     * @param string|null $db_connection Which database connection to use
     * @throws InvalidArgumentException if database table details are not provided
     */
    protected function _setTable($table = '', $db_connection = null)
    {

        if ($table == '')
        {
            throw new InvalidArgumentException('Database table details not provided');
        }

        $this->_bourbon_orm_object_dependencies            = new stdClass();
        $this->_bourbon_orm_object_dependencies->db        = Instance::_retrieve(Database::class);
        $this->_bourbon_orm_object_dependencies->validator = Instance::_retrieve(Validator::class);

        if (!is_null($db_connection))
        {
            $this->_bourbon_orm_object_dependencies->db = $this->_bourbon_orm_object_dependencies->db->swap($db_connection);
        }

        $this->_bourbon_orm_object_table = strtolower($table);

        $this->_setUpProperties();

    }


    /**
     * Make a note of usable properties for the object
     */
    protected function _setUpProperties()
    {

        $db         = $this->_bourbon_orm_object_dependencies->db;
        $properties = $db->getFields($this->_bourbon_orm_object_table);
        $properties = array_map('strtolower', $properties);

        $this->_bourbon_orm_object_properties = $properties;

    }


    /**
     * Set the primary key of the database table
     * @param string $primary_key Primary key of table
     * @throws InvalidArgumentException if a primary key was not provided
     */
    protected function _setPrimaryKey($primary_key = '')
    {

        if ($primary_key == '')
        {
            throw new InvalidArgumentException('Primary key not provided');
        }

        $this->_bourbon_orm_object_primary_key = strtolower($primary_key);

    }


    /**
     * Store the primary key that came from the record, if available
     * @return mixed Primary key value (if available)
     */
    protected function _rememberPrimaryKeyValue()
    {

        if (isset($this->_bourbon_orm_object_record_details[$this->_bourbon_orm_object_primary_key]))
        {
            $this->_bourbon_orm_object_primary_key_value = $this->_bourbon_orm_object_record_details[$this->_bourbon_orm_object_primary_key];
        }

        return $this->_bourbon_orm_object_primary_key_value;

    }


    /**
     * Define a 'belongs to' relationship for the model
     * @param string $property  Name of property to use to access related model(s)
     * @param string $orm_class Fully-qualified class name of related model
     * @param string $this_key  Name of key of this model used in the relationship
     * @param string $that_key  Name of key of related model used in the relationship
     */
    protected function _belongsTo($property = '', $orm_class = '', $this_key = '', $that_key = '')
    {

        $this->_addRelationship('belongs_to', $property, $orm_class, $this_key, $that_key);

    }


    /**
     * Define a 'has' relationship for the model
     * @param string $property  Name of property to use to access related model(s)
     * @param string $orm_class Fully-qualified class name of related model
     * @param string $this_key  Name of key of this model used in the relationship
     * @param string $that_key  Name of key of related model used in the relationship
     */
    protected function _has($property = '', $orm_class = '', $this_key = '', $that_key = '')
    {

        $this->_addRelationship('has', $property, $orm_class, $this_key, $that_key);

    }


    /**
     * Add a relationship to the model
     * @param string $type      Type of relationship
     * @param string $property  Name of property to use to access related model(s)
     * @param string $orm_class Fully-qualified class name of related model
     * @param string $this_key  Name of key of this model used in the relationship
     * @param string $that_key  Name of key of related model used in the relationship
     * @throws InvalidArgumentException if invalid relationship details are provided
     */
    protected function _addRelationship($type = '', $property = '', $orm_class = '', $this_key = '', $that_key = '')
    {

        if ($property  == '' OR
            $orm_class == '' OR
            $this_key  == '' OR
            $that_key  == '')
        {
            throw new InvalidArgumentException('ORM object details not provided');
        }

        $property  = strtolower($property);
        $orm_class = '\\' . ltrim($orm_class, '\\');

        /*
         * Check for naming collisions
         */
        if (in_array($property, $this->_bourbon_orm_object_properties) OR
            isset($this->_bourbon_orm_object_relationships[$property]))
        {
            throw new InvalidArgumentException('Related object property name \'' . $property . '\' clashes with an existing object property');
        }

        /*
         * Check that the related object extends this class
         */
        if (!is_subclass_of($orm_class, self::class))
        {
            throw new InvalidArgumentException('Invalid ORM class');
        }

        $relationship_details =
            [
                'property' => $property,
                'class'    => $orm_class,
                'this_key' => $this_key,
                'that_key' => $that_key
            ];

        if ($type == 'belongs_to')
        {
            $this->_bourbon_orm_object_belongs_to[$property] = $relationship_details;
        }

        else if ($type == 'has')
        {
            $this->_bourbon_orm_object_has[$property] = $relationship_details;
        }

        if (!isset($this->_bourbon_orm_object_relationships[$property]))
        {
            $this->_bourbon_orm_object_relationships[$property] = [];
        }

    }


    /**
     * Get details of a relationship
     * @param  string $property Relationship property name to look for
     * @return array            Array of relationship details
     * @throws InvalidArgumentException if the relationship does not exist
     */
    protected function _getRelationshipDetails($property = '')
    {

        $property = strtolower($property);

        if (isset($this->_bourbon_orm_object_belongs_to[$property]))
        {
            return $this->_bourbon_orm_object_belongs_to[$property];
        }

        else if (isset($this->_bourbon_orm_object_has[$property]))
        {
            return $this->_bourbon_orm_object_has[$property];
        }

        throw new InvalidArgumentException('Relationship \'' . $property . '\' does not exist');

    }


    /**
     * Add a validation rule for a property
     * @param string      $property   Property to validate
     * @param string      $validator  Validation rule to utilise
     * @param string|null $comparison Comparison value
     */
    protected function _addValidator($property = '', $validator = '', $comparison = null)
    {

        $property         = strtolower($property);
        $validation_array = ['rule' => $validator, 'comparison' => $comparison];

        $this->_bourbon_orm_object_validators[$property][] = $validation_array;

    }


    /**
     * Run validation on a property
     * @param  string $property Property to validate
     * @param  string $value    Value to validate
     * @return bool             Whether the property passes all validation rules
     */
    protected function _validateProperty($property = '', $value = '')
    {

        $property              = strtolower($property);
        $single_property_array = [$property => $value];
        $validator             = $this->_bourbon_orm_object_dependencies->validator;

        $validator->reset();
        $validator->setInputArray($single_property_array);

        if (isset($this->_bourbon_orm_object_validators[$property]))
        {

            foreach ($this->_bourbon_orm_object_validators[$property] as $validation_rule_array)
            {

                $rule       = $validation_rule_array['rule'];
                $comparison = $validation_rule_array['comparison'];

                $temp_validator = $validator->add($property)->type($rule)->required();

                if (!is_null($comparison))
                {
                    $temp_validator->compare($comparison);
                }

            }

            return $validator->passed();

        }

        return true;

    }


    /**
     * Check whether a table link has been supplied
     * @return bool Whether a table link has been supplied
     */
    protected function _isActive()
    {

        if (is_null($this->_bourbon_orm_object_table) OR
            is_null($this->_bourbon_orm_object_primary_key))
        {
            return false;
        }

        return true;

    }


    /**
     * Find [a] record(s) and return [a] populated object(s)
     * @param  string|Closure $id_or_object Primary key ID (or closure, if building a query)
     * @return Template                     Populated ORM Template object
     * @throws RecordNotFoundException if no objects could be found
     */
    public static function find($id_or_object = '')
    {

        /*
         * Multiple records
         */
        if ((is_object($id_or_object) AND ($id_or_object instanceof Closure)))
        {
            return static::_findByQuery($id_or_object);
        }

        /*
         * Single records
         */
        else
        {

            try
            {

                $class           = get_called_class();
                $object          = new $class();
                $stored_instance = self::_getStoredInstance($class, $id_or_object);

                /*
                 * Use stored instance
                 */
                if (!is_null($stored_instance))
                {
                    return $stored_instance;
                }

                /*
                 * Instantiate a new object
                 */
                else
                {

                    $object->_populateById($id_or_object);

                    return $object;

                }


            }

            catch (Exception $exception) {}

        }

        throw new RecordNotFoundException('No objects found');

    }


    /**
     * Get a stored instance
     * @param  string   $class Name of ORM class
     * @param  mixed    $id    Instance ID (primary key)
     * @return Template        Instance object, descended from Template (or NULL if not available)
     */
    protected static function _getStoredInstance($class = '', $id = null)
    {

        if (isset(self::$_bourbon_orm_stored_instances[$class][$id]))
        {
            return self::$_bourbon_orm_stored_instances[$class][$id];
        }

        return null;

    }


    /**
     * Retrieve all objects
     * @return array Array of ORM Template-descended objects
     */
    public static function all()
    {

        $class       = get_called_class();
        $object      = new $class();
        $objects     = [];
        $primary_key = $object->getPrimaryKey();

        $db = $object->_bourbon_orm_object_dependencies->db;

        $results = $db->build()
                      ->table($object->_bourbon_orm_object_table)
                      ->select();

        $count = 0;

        foreach ($results as $result)
        {

            $stored_instance = self::_getStoredInstance($class, $result[$primary_key]);

            /*
             * Use stored instance
             */
            if (!is_null($stored_instance))
            {
                $objects[$count] = $stored_instance;
            }

            /*
             * Instantiate a new object
             */
            else
            {
                $objects[$count] = new $class();
                $objects[$count]->_populateDetails($result);
            }

            $count++;

        }

        return $objects;

    }


    /**
     * Retrieve all objects, with pagination enabled
     * @param  int   $limit Pagination limit
     * @return array        Array of ORM Template-descended objects
     * @throws InvalidArgumentException if the limit is not valid
     */
    public static function paginate($limit = 10)
    {

        if ((int)$limit < 1)
        {
            throw new InvalidArgumentException('Invalid pagination limit');
        }

        $class       = get_called_class();
        $object      = new $class();
        $objects     = [];
        $primary_key = $object->getPrimaryKey();

        $db = $object->_bourbon_orm_object_dependencies->db;

        $results = $db->build()
                      ->table($object->_bourbon_orm_object_table)
                      ->paginate($limit)
                      ->select();

        $count = 0;

        foreach ($results as $result)
        {

            $stored_instance = self::_getStoredInstance($class, $result[$primary_key]);

            /*
             * Use stored instance
             */
            if (!is_null($stored_instance))
            {
                $objects[$count] = $stored_instance;
            }

            /*
             * Instantiate a new object
             */
            else
            {
                $objects[$count] = new $class();
                $objects[$count]->_populateDetails($result);
            }


            $count++;

        }

        return $objects;

    }


    /**
     * Chunk and retrieve all objects
     * @param  int     $limit    Number of records to retrieve at a time
     * @param  Closure $callback Closure to pass each array of ORM Template objects to
     * @throws InvalidArgumentException if the limit is not valid
     * @throws InvalidArgumentException if the callback is not valid
     */
    public static function chunk($limit = 10, Closure $callback)
    {

        if (!(is_object($callback) AND ($callback instanceof Closure)))
        {
            throw new InvalidArgumentException('Invalid caded');
        }

        if ((int)$limit < 1)
        {
            throw new InvalidArgumentException('Invalid');
        }

        $class       = get_called_class();
        $object      = new $class();
        $primary_key = $object->getPrimaryKey();

        $db = $object->_bourbon_orm_object_dependencies->db;

        $count  = 0;
        $offset = 0;
        $max    = $db->build()
                     ->table($object->_bourbon_orm_object_table)
                     ->count();

        while ($offset < $max)
        {

            $objects = [];

            $results = $db->build()
                          ->table($object->_bourbon_orm_object_table)
                          ->startAt($offset)
                          ->fetch($limit)
                          ->select();

            foreach ($results as $result)
            {

                $stored_instance = self::_getStoredInstance($class, $result[$primary_key]);

                /*
                 * Use stored instance
                 */
                if (!is_null($stored_instance))
                {
                    $objects[$count] = $stored_instance;
                }

                /*
                 * Instantiate a new object
                 */
                else
                {
                    $objects[$count] = new $class();
                    $objects[$count]->_populateDetails($result);
                }


                $count++;

            }

            $callback($objects);

            unset($objects);

            $offset += $limit;

        }

    }


    /**
     * Retrieve objects by building a query
     * @param  Closure $query_builder Closure into which to inject a QueryBuilder object
     * @return array                  Array of ORM Template-descended objects
     * @throws InvalidArgumentException if the closure is not valid
     */
    protected static function _findByQuery(Closure $query_builder)
    {

        $class       = get_called_class();
        $object      = new $class();
        $objects     = [];
        $primary_key = $object->getPrimaryKey();

        if (!(is_object($query_builder) AND ($query_builder instanceof Closure)))
        {
            throw new InvalidArgumentException('Invalid query builder closure');
        }

        /*
         * Start off the query
         */
        $db = $object->_bourbon_orm_object_dependencies
                     ->db
                     ->build()
                     ->table($object->_bourbon_orm_object_table);

        /*
         * Pass it to the closure and execute
         */
        $query_builder($db);

        $results = $db->select();

        $count = 0;

        foreach ($results as $result)
        {

            $stored_instance = self::_getStoredInstance($class, $result[$primary_key]);

            /*
             * Use stored instance
             */
            if (!is_null($stored_instance))
            {
                $objects[$count] = $stored_instance;
            }

            /*
             * Instantiate a new object
             */
            else
            {
                $objects[$count] = new $class();
                $objects[$count]->_populateDetails($result);
            }


            $count++;

        }

        return $objects;

    }


    /**
     * Populate the object with a specific record
     * @param string $id                            Primary key ID
     * @param bool   $force_relationship_population Whether to force repopulation of related objects
     * @throws RecordNotFoundException if the record cannot be found in the database
     */
    protected function _populateById($id = '', $force_relationship_population = false)
    {

        $db = $this->_bourbon_orm_object_dependencies->db;

        $result = $db->build()
                     ->table($this->_bourbon_orm_object_table)
                     ->where($this->_bourbon_orm_object_primary_key, $id)
                     ->fetch(1)
                     ->select();

        $result = reset($result);

        if (!isset($result[$this->_bourbon_orm_object_primary_key]))
        {
            throw new RecordNotFoundException('Record \'' . $id . '\' does not exist');
        }

        $this->_populateDetails($result, $force_relationship_population);

    }


    /**
     * Populate the object with details from the database
     * @param array $data                          Database result
     * @param bool  $force_relationship_population Whether to force repopulation of related objects
     * @throws EngineNotInitialisedException if the object has not been initialised
     */
    protected function _populateDetails(array $data = [], $force_relationship_population = false)
    {

        if (!$this->_isActive())
        {
            throw new EngineNotInitialisedException('Object has not been initialised');
        }

        /*
         * Assign the data
         */
        $this->_bourbon_orm_object_record_details = array_change_key_case($data, CASE_LOWER);
        $primary_key_value                        = $this->_rememberPrimaryKeyValue();

        /*
         * Populate the object with any related objects
         */
        if ($force_relationship_population)
        {
            $this->_populateRelationships();
        }

        /*
         * Store the object for reuse
         */
        $class = get_class($this);

        if (!isset(self::$_bourbon_orm_stored_instances[$class]))
        {
            self::$_bourbon_orm_stored_instances[$class] = [];
        }

        if (!is_null($primary_key_value))
        {
            self::$_bourbon_orm_stored_instances[$class][$primary_key_value] = $this;
        }

    }


    /**
     * Populate an array with the various relationships of the object
     */
    protected function _populateRelationships()
    {

        $relationships = ['belongs_to' => $this->_bourbon_orm_object_belongs_to,
                          'has'        => $this->_bourbon_orm_object_has];

        foreach ($relationships as $type => $relationship)
        {

            foreach ($relationship as $relationship_details)
            {

                $class_name = $relationship_details['class'];
                $that_key   = $relationship_details['that_key'];
                $this_key   = $relationship_details['this_key'];
                $property   = $relationship_details['property'];
                $this_value = $this->$this_key;

                $objects = $class_name::_findByQuery(function($query_builder) use ($that_key, $this_value)
                {
                    return $query_builder->where($that_key, $this_value);
                }, false);

                /*
                 * Reset the array of related objects
                 */
                foreach ($this->_bourbon_orm_object_relationships as &$existing_related_objects)
                {
                    $existing_related_objects = [];
                }

                /*
                 * Add related objects
                 */
                foreach ($objects as $object)
                {
                    $this->_bourbon_orm_object_relationships[$property][] = $object;
                }

            }

        }

    }


    /**
     * Get an array of the object's data
     * @return array Array of object data
     */
    public function getDataArray()
    {

        return $this->_bourbon_orm_object_record_details;

    }


    /**
     * Get the object's table name
     * @return string Object table name
     */
    public function getTable()
    {

        return $this->_bourbon_orm_object_table;

    }


    /**
     * Get the object's primary key name
     * @return string Object primary key name
     */
    public function getPrimaryKey()
    {

        return $this->_bourbon_orm_object_primary_key;

    }


    /**
     * Get a specific property from the object
     * @param  string $name Name of property
     * @return mixed        Property value
     * @throws EngineNotInitialisedException if the object has not been initialised
     * @throws InvalidArgumentException if the property does not exist
     */
    public function __get($name = '')
    {

        if (!$this->_isActive())
        {
            throw new EngineNotInitialisedException('Object has not been initialised');
        }

        $name = strtolower($name);

        /*
         * First deal with relationships
         */
        if (isset($this->_bourbon_orm_object_relationships[$name]))
        {

            if (empty($this->_bourbon_orm_object_relationships[$name]))
            {
                $this->_populateRelationships();
            }

            return $this->_bourbon_orm_object_relationships[$name];
        }

        /*
         * Then deal with actual properties
         */
        else if (!isset($this->_bourbon_orm_object_record_details[$name]))
        {

            /*
             * Check to see if the property is valid but has not yet been set
             */
            if (in_array($name, $this->_bourbon_orm_object_properties))
            {
                return '';
            }

            else
            {
                throw new InvalidArgumentException('Invalid object property \'' . $name . '\'');
            }

        }

        /*
         * Return the property
         */
        return $this->_bourbon_orm_object_record_details[$name];

    }


    /**
     * Add a property to the object's blacklist
     * @param string|array $property Name of property (or array of property names)
     * @throws EngineNotInitialisedException if the object has not been initialised
     */
    protected function _addToBlacklist($property = '')
    {

        if (!$this->_isActive())
        {
            throw new EngineNotInitialisedException('Object has not been initialised');
        }

        /*
         * Wildcard
         */
        if ($property == '*')
        {
            $this->_addToBlacklist($this->_bourbon_orm_object_properties);
        }

        /*
         * Array
         */
        else if (is_array($property))
        {

            foreach ($property as $property_string)
            {
                $this->_addToBlacklist($property_string);
            }

        }

        /*
         * Single property
         */
        else
        {
            $this->_bourbon_orm_object_blacklist[] = strtolower($property);
        }

    }


    /**
     * Get the type of relationship that a property has to the object
     * @param  string $property Property name
     * @return string           Type of relationship
     * @throws InvalidArgumentException if the relationship does not exist
     */
    protected function _getRelationshipType($property = '')
    {

        $property = strtolower($property);

        if (isset($this->_bourbon_orm_object_belongs_to[$property]))
        {
            return 'belongs_to';
        }

        else if (isset($this->_bourbon_orm_object_has[$property]))
        {
            return 'has';
        }

        throw new InvalidArgumentException('Relationship \'' . $property . '\' does not exist');

    }


    /**
     * Set a specific property for the object
     * @param string $name  Name of property
     * @param mixed  $value Property value
     * @throws EngineNotInitialisedException if the object has not been initialised
     * @throws InvalidArgumentException if the property does not exist
     */
    public function __set($name = '', $value = null)
    {

        if (!$this->_isActive())
        {
            throw new EngineNotInitialisedException('Object has not been initialised');
        }

        $name = strtolower($name);

        if (in_array($name, $this->_bourbon_orm_object_blacklist))
        {
            throw new InvalidArgumentException('Cannot write to blacklisted property \'' . $name . '\'');
        }

        /*
         * First deal with relationships
         */
        if (isset($this->_bourbon_orm_object_relationships[$name]))
        {

            $details       = $this->_getRelationshipDetails($name);
            $that_property = $details['that_key'];
            $this_property = $details['this_key'];
            $primary_class = $details['class'];
            $foreign_class = '\\' . ltrim(get_class($value), '\\');

            /*
             * Check that the type is correct
             */
            if ($primary_class != $foreign_class)
            {
                throw new InvalidArgumentException('Could not assign object to property; invalid class type');
            }

            /*
             * Update the foreign key
             */
            try
            {

                if ($this->_getRelationshipType($name) == 'has')
                {
                    $value->$that_property = $this->$this_property;
                }

                else
                {

                    /*
                     * Save the 'parent' relation to get the ID to populate the
                     * foreign key
                     */
                    $value->save();

                    $this->$this_property = $value->$that_property;

                }

            }

            catch (Exception $exception) {}

            /*
             * Save the entry
             */
            $this->_bourbon_orm_object_relationships[$name][] = $value;

        }

        /*
         * Then deal with actual properties
         */
        else if (!in_array($name, $this->_bourbon_orm_object_properties))
        {
            throw new InvalidArgumentException('Invalid object property \'' . $name . '\'');
        }

        else if (!$this->_validateProperty($name, $value))
        {
            throw new InvalidArgumentException('Cannot write to property \'' . $name . '\'; invalid value \'' . $value . '\'');
        }

        else
        {
            $this->_bourbon_orm_object_record_details[$name] = $value;
        }

    }


    /**
     * Save any changes to the object back to the database
     * @param  bool $save_related Whether to also save related records
     * @return bool               Whether the database was successfully updated
     * @throws EngineNotInitialisedException if the object has not been initialised
     */
    public function save($save_related = false)
    {

        if (!$this->_isActive())
        {
            throw new EngineNotInitialisedException('Object has not been initialised');
        }

        /*
         * First deal with requests to save related records
         */
        if ($save_related)
        {
            return $this->_saveThisAndRelated();
        }

        /*
         * Otherwise just save this record
         */
        $db              = $this->_bourbon_orm_object_dependencies->db;
        $primary_key_set = isset($this->_bourbon_orm_object_record_details[$this->_bourbon_orm_object_primary_key]);
        $update          = $primary_key_set;

        /*
         * See if a record with the set primary key exists -- if not, make a
         * note to perform an insertion, rather than an update
         */
        if ($primary_key_set)
        {

            $update = $db->build()
                         ->table($this->_bourbon_orm_object_table)
                         ->where($this->_bourbon_orm_object_primary_key, $this->_bourbon_orm_object_primary_key_value)
                         ->exists();

        }

        try
        {

            $query = $db->build()
                        ->table($this->_bourbon_orm_object_table)
                        ->data($this->_bourbon_orm_object_record_details);

            /*
             * Update an existing record
             */
            if ($update)
            {

                /*
                 * Save the record against the existing primary key
                 */
                $primary_key = $this->_bourbon_orm_object_primary_key_value;
                $query       = $query->where($this->_bourbon_orm_object_primary_key, $primary_key);

                $query->update();

                /*
                 * Reload the record using the new primary key
                 */
                $primary_key = $this->_bourbon_orm_object_record_details[$this->_bourbon_orm_object_primary_key];

                $this->_populateById($primary_key, true);

            }

            /*
             * Insert a new record
             */
            else
            {

                /*
                 * Save the record
                 */
                $auto_id         = $query->insert();
                $increment_field = $db->getAutoIncrementField($this->_bourbon_orm_object_table);

                /*
                 * Reload the record
                 */
                if ($increment_field !== false)
                {

                    $increment_field = strtolower($increment_field);

                    $result = $db->build()
                                 ->table($this->_bourbon_orm_object_table)
                                 ->where($increment_field, $auto_id)
                                 ->fetch(1)
                                 ->select();

                    $result = reset($result);

                    $this->_populateDetails($result, true);

                }

            }

            return true;

        }

        catch (Exception $exception) {}

        return false;

    }


    /**
     * Save any changes to the object (and its related objects) back to the
     * database
     * @return bool Whether all objects were successfully updated
     * @throws EngineNotInitialisedException if the object has not been initialised
     */
    protected function _saveThisAndRelated()
    {

        if (!$this->_isActive())
        {
            throw new EngineNotInitialisedException('Object has not been initialised');
        }

        /*
         * Save related objects
         */
        foreach ($this->_bourbon_orm_object_relationships as $relationship_array)
        {

            foreach ($relationship_array as $relationship_object)
            {

                if (!$relationship_object->save())
                {
                    return false;
                }

            }

        }

        /*
         * Save this object
         */
        return $this->save();

    }


    /**
     * Delete the record from the database
     * @param  bool $delete_related Whether to also delete related records
     * @return bool                 Whether the record was successfully deleted
     * @throws EngineNotInitialisedException if the object has not been initialised
     * @throws RecordNotFoundException if the record does not exist
     */
    public function delete($delete_related = false)
    {

        if (!$this->_isActive())
        {
            throw new EngineNotInitialisedException('Object has not been initialised');
        }

        if (!isset($this->_bourbon_orm_object_record_details[$this->_bourbon_orm_object_primary_key]))
        {
            throw new RecordNotFoundException('Record does not exist');
        }

        /*
         * First deal with requests to delete related records
         */
        if ($delete_related)
        {
            return $this->_deleteThisAndRelated();
        }

        /*
         * Otherwise just delete this record
         */
        $db          = $this->_bourbon_orm_object_dependencies->db;
        $primary_key = $this->_bourbon_orm_object_record_details[$this->_bourbon_orm_object_primary_key];

        try
        {

            $db->build()
               ->table($this->_bourbon_orm_object_table)
               ->where($this->_bourbon_orm_object_primary_key, $primary_key)
               ->delete();

            /*
             * Clear all stored instances, so this record cannot be retrieved
             * again
             */
            self::clearCache();

            return true;

        }

        catch (Exception $exception) {}

        return false;

    }


    /**
     * Delete the record (and its related records)
     * @return bool Whether all records were successfully deleted
     * @throws EngineNotInitialisedException if the record has not been initialised
     */
    protected function _deleteThisAndRelated()
    {

        if (!$this->_isActive())
        {
            throw new EngineNotInitialisedException('Object has not been initialised');
        }

        /*
         * Delete related records
         */
        $this->_populateRelationships();

        foreach ($this->_bourbon_orm_object_relationships as $relationship_array)
        {

            foreach ($relationship_array as $relationship_object)
            {

                if (!$relationship_object->delete())
                {
                    return false;
                }

            }

        }

        /*
         * Delete this record
         */
        return $this->delete();

    }


    /**
     * Clear the store of instance objects
     */
    public static function clearCache()
    {

        self::$_bourbon_orm_stored_instances = [];

    }


}