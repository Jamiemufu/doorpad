<?php


namespace Itg\DoorPad;


use InvalidArgumentException;
use Whiskey\Bourbon\App\Facade\Db;
use Whiskey\Bourbon\App\Facade\Url;
use Whiskey\Bourbon\App\Facade\Storage as FileStorage;


/**
 * Event class
 * @package Itg\DoorPad
 */
class Visitor
{

    protected static $_table          = 'visitors';
    protected $_properties = [];


    /**
     * Instantiate a new Award instance
     * @param int $id Award ID
     */
    public function __construct($id = 0)
    {

        $record = Db::build()->table(static::$_table)
                             ->where('id', $id)
                             ->select();

        if (!isset($record[$id]))
        {
            throw new InvalidArgumentException('Visitor does not exist');
        }

        $this->_properties = $record[$id];

    }


    /**
     * Create a visitor
     * @param  string $title Award title (name)
     * @return self          Award instance
     */
    public static function create($post = array())
    {

        $id = Db::build()->table(static::$_table)
                         ->data('first_name', trim(strtolower($post['first_name'])))
                         ->data('last_name', trim(strtolower($post['last_name'])))
                         ->data('badge', trim(strtolower($post['badge'])))
                         ->data('company', trim(strtolower($post['company'])))
                         ->data('visiting', trim(strtolower($post['visiting'])))                         
                         ->data('signedInTime', date('Y-m-d G:i:s'))
                         ->data('signedIn', 1)
                         ->data('carReg', trim(strtoupper($post['carReg'])))
                         ->insert();

        return new static($id);

    }

     /**
     * Sign out a visitor
     * @param  string $title Award title (name)
     * @return self          Award instance
     */
    public static function signOut($id)
    {
        
        // change signedIn field to 0
         Db::build()->table(static::$_table)
                    ->where('id', $id)
                    ->data('signedIn', 0)
                    ->data('signedOutTime', date('Y-m-d G:i:s'))
                    ->update();
    }

    /**
    * Sign all visitors out
    *
    */
    public static function signOutAllVisitors()
    {
        /**
        * Sign visitors out from database
        *
        */
         Db::build()->table(static::$_table)
                    ->where('signedIn', 1)
                    ->data('signedIn', 0)
                    ->data('signedOutTime', date('Y-m-d G:i:s'))
                    ->update();

    }

    /**
    * Get visiors between dates
    * @param date $start_date
    * @param date $end_date
    */
    public static function getVisitorsDate($start_date, $end_date)
    {
        /**
        * Get visitors from date range
        *
        */
        $result = Db::build()->table(static::$_table)
                    ->whereGreaterThan('signedInTime', $start_date)
                    ->whereLessThan('signedInTime', $end_date)                                                
                    ->select();

        return $result;
    }

    /**
     * Get the award ID
     * @return int Award ID
     */
    public function getId()
    {

        return $this->_properties['id'];

    }


    /**
     * Get the award ID
     * @return int Award ID
     */
    public function getFirstName()
    {

        return ucfirst(strtolower($this->_properties['first_name']));

    }

    /**
     * Get the award ID
     * @return int Award ID
     */
    public function getLastName()
    {

        return ucfirst(strtolower($this->_properties['last_name']));

    }

    /**
     * Get the award ID
     * @return int Award ID
     */
    public function getCompany()
    {

        return ucfirst(strtolower($this->_properties['company']));

    }

    /**
     * Get the award ID
     * @return int Award ID
     */
    public function getVisiting()
    {

        return ucfirst(strtolower($this->_properties['visiting']));

    }

    /**
     * Get the award ID
     * @return int Award ID
     */
    public function getBadge()
    {

        return strtolower($this->_properties['badge']);

    }  

    /**
     * Get all visitors
     * @param  string $order              Award order (ASC or DESC)
     * @return array                      Array of initials with array of Visitor objects
     */
    public static function getAll($order = 'ASC')
    {
    	$directory = [];
    	foreach( range('a','z') as $letter )
    	{
    		$directory[$letter] = [];
    	}

        $result = [];
        $order  = (strtoupper($order) == 'ASC') ? 'ASC' : 'DESC';

        $query = Db::build()->table(static::$_table)
                            ->where('signedIn', 1)
                            ->orderBy('first_name', $order);

        $records = $query->select('id');

        foreach ($records as $record)
        {
            $result[] = new static($record['id']);
        }

        foreach ($result as $record) {
        	$initial = strtolower(substr($record->getFirstName(), 0, 1));
        	$directory[$initial][] = $record;
        }

        return $directory;

    }


    /**
     * Delete a visitor
     */
    public static function delete($id)
    {

        /*
         * Delete visitor from database
         */
        Db::build()->table(static::$_table)
                   ->where('id', $id)
                   ->delete();

    }
    
    
}