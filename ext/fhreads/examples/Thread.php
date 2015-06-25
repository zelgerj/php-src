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
     * Defines thread states
     *
     * @var int
     */
    const STATE_STARTED = 1;
    const STATE_RUNNING = 2;
    const STATE_WAITING = 4;
    const STATE_JOINED = 8;
    const STATE_ERROR = 16;

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
     * Holds thread state flag
     *
     * @var int
     */
    protected $state = 0;

    /**
     * Abstract run function
     *
     * @return void
     */
    abstract function run();

    /**
     * Start method which will prepare, create and starts a thread
     *
     * @return boolean pthread create state
     */
    public function start()
    {
        // check if was started already
        if ($this->getState() >= self::STATE_STARTED) {
            throw new \Exception('Thread has been started already!');
        }
        $this->setState(self::STATE_STARTED);
        // init thread mutex
        $this->mutex = fhread_mutex_init();
        // create, start thread and save thread id
        $status = fhread_create($this, $this->id);
        if ($status === 0) {
            $this->setState(self::STATE_RUNNING);
            return true;
        }
        $this->setState(self::STATE_ERROR);
        return false;
    }

    /**
     * Returns the current state of thread object
     *
     * @return int
     */
    public function getState() {
        $this->lock();
        $state = $this->state;
        $this->unlock();
        return $state;
    }

    /**
     * Sets the given state for thread object
     *
     * @return void
     */
    public function setState($state) {
        $this->lock();
        $this->state = $state;
        $this->unlock();
    }

    /**
     * Joins the current thread by its thread id.
     *
     * @return void
     */
    public function join()
    {
        // check if was started already
        if ($this->getState() >= self::STATE_JOINED) {
            throw new \Exception('Thread has joined already!');
        }
        // only if thread was started before
        if ($this->getState() < self::STATE_STARTED) {
            throw new \Exception('Thread has not been started yet!');
        }

        fhread_join($this->getThreadId());
        $this->setState(self::STATE_JOINED);
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
     * Executes given closure synchronized
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
     * Returns the objects threads id
     *
     * @return int
     */
    public function getThreadId()
    {
        return $this->id;
    }

    /**
     * Returns current thread id
     */
    static public function getCurrentThreadId()
    {
        return fhread_self();
    }

    /**
     * Destructs the object after the threads run method has been executed
     *
     * @return void
     */
    public function __destruct()
    {
        // check if thread is between joined and started state to join it automatically
        // if php process is going to shutdown
        if (($this->getState() > self::STATE_STARTED) && $this->getState() < self::STATE_JOINED) {
            $this->join();
        }
        // in every other case do nothing
    }

}
