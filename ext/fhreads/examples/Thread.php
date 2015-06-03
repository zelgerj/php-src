<?php

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io-php/fhreads
 * @link      http://www.appserver.io
 */

/**
 * Interface Runnable
 * 
 * Simple interface for runnables
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/fhreads
 * @link      http://www.appserver.io
 */
interface Runnable
{
    public function run();
}

/**
 * Abstract Class Thread
 *
 * Simple thread abstract class which implements runnable interface
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/fhreads
 * @link      http://www.appserver.io
 */
abstract class Thread implements Runnable
{
    /**
     * Holds thread id if started
     * 
     * @var int
     */
    protected $id = null;
    
    /**
     * Holds thread mutex pointer
     * 
     * @var int
     */
    protected $mutex = null;
    
    /**
     * Abstract run function
     * 
     * @return void
     */
    abstract function run();
    
    /**
     * Start method which will prepare, create and starts a thread
     * 
     * @return int pthread create status
     */
    public function start()
    {
        // init thread mutex
        $this->mutex = fhread_mutex_init();
        // create, start thread and save thread id 
        $status = fhread_create($this, $this->id);
    }
    
    /**
     * Joins the current thread by its thread id.
     *
     * @return void
     */
    public function join()
    {
        // join if thread id is not null
        if (!is_null($this->getThreadId())) {
            fhread_join($this->getThreadId());
        }
    }
    
    /**
     * Locks thread object's mutex
     * 
     * @return void
     */
    public function lock()
    {
        if (!is_null($this->getMutex())) {
            fhread_mutex_lock($this->getMutex());
        }
    }
    
    /**
     * Unlocks thread object's mutex
     * 
     * @return void
     */
    public function unlock()
    {
        if (!is_null($this->getMutex())) {
            fhread_mutex_unlock($this->getMutex());
        }
    }
    
    /**
     * 
     * @param callable $sync
     */
    public function synchronized(\Closure $sync)
    {
        $this->lock();
        $sync($this);
        $this->unlock();
    }
    
    /**
     * Returns the thread mutex
     * 
     * @return int
     */
    public function getMutex()
    {
        return $this->mutex;
    }
    
    /**
     * Returns the threads id
     * 
     * @return int
     */
    public function getThreadId()
    {
        return $this->id;
    }
    
}
