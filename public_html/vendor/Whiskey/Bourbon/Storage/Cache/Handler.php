<?php


namespace Whiskey\Bourbon\Storage\Cache;


use Whiskey\Bourbon\App\DefaultHandlerAbstract;


/**
 * Cache Handler class
 * @package Whiskey\Bourbon\Storage\Cache
 */
class Handler extends DefaultHandlerAbstract
{


    /**
     * Set the typehint for the handler
     */
    public function __construct()
    {

        $this->_type_hint = CacheInterface::class;

    }
    
    
    /**
     * Clear all cache entries
     */
    public function clearAll()
    {

        foreach ($this->getEngines() as $engine)
        {

            if ($engine['engine']->isActive())
            {
                $engine['engine']->clearAll();
            }

        }

    }


}

