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
    protected $threadId = null;
    protected $fhreadHandle = null;

    /**
     * Holds thread mutex pointers
     *
     * @var int
     */
    protected $globalMutex = null;
    protected $stateMutex = null;
    protected $syncMutex = null;
    
    /**
     * Holds thread condition pointer
     * 
     * @var int
     */
    protected $syncNotify = null;

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
        if (($this->getState() >= self::STATE_STARTED)
            && ($this->getState() < self::STATE_JOINED)) {
            throw new \Exception('Thread has been started already!');
        }
        $this->setState(self::STATE_STARTED);
        // init thread cond
        $this->syncNotify = fhread_cond_init();
        // init thread mutex
        $this->globalMutex = fhread_mutex_init();
        $this->stateMutex = fhread_mutex_init();
        $this->syncMutex = fhread_mutex_init();
        // create, start thread and save thread id
        if (fhread_create($this, $this->threadId, $this->fhreadHandle) === 0) {
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
    public function getState()
    {
        fhread_mutex_lock($this->stateMutex);
        $state = $this->state;
        fhread_mutex_unlock($this->stateMutex);
        return $state;
    }

    /**
     * Sets the given state for thread object
     *
     * @return void
     */
    public function setState($state)
    {
        fhread_mutex_lock($this->stateMutex);
        $this->state = $state;
        fhread_mutex_unlock($this->stateMutex);
    }
    
    /**
     * Checks if state matches given state
     * 
     * @param int $state The state that should match current state
     * 
     * @return boolean
     */
    public function hasState($state)
    {
        fhread_mutex_lock($this->stateMutex);
        $hasState = ($this->state === $state);
        fhread_mutex_unlock($this->stateMutex);
        return $hasState;
    }

    /**
     * Joins the current thread by its thread id.
     *
     * @return boolean
     */
    public function join()
    {
        // check if was started already
        if ($this->getState() >= self::STATE_JOINED) {
            return;
        }
        // only if thread was started before
        if ($this->getState() < self::STATE_STARTED) {
            throw new \Exception('Thread has not been started yet!');
        }

        if (fhread_join($this->fhreadHandle, $rv) === 0) {
            // check if error occured in run method
            if ($rv != 0) {
                $this->setState(self::STATE_ERROR);
            } else {
                $this->setState(self::STATE_JOINED);
            }
            return true;
        }
        
        $this->setState(self::STATE_ERROR);
        return false;
    }
    
    /**
     * Detaches the current thread by its thread id.
     *
     * @return boolean
     */
    public function detach()
    {
        // check if was started already
        if ($this->getState() >= self::STATE_JOINED) {
            return;
        }
        // only if thread was started before
        if ($this->getState() < self::STATE_STARTED) {
            throw new \Exception('Thread has not been started yet!');
        }
    
        if (fhread_detach($this->fhreadHandle) === 0) {
            return true;
        }
    
        $this->setState(self::STATE_ERROR);
        return false;
    }
    
    /**
     * Sends a kill signal to get killed
     *
     * @return boolean
     */
    public function kill()
    {
        return fhread_kill($this->fhreadHandle);
    }
    
    /**
     * Returns wheater the thread is in waiting state or not
     * 
     * @return bool
     */
    public function isWaiting()
    {
        return $this->hasState(self::STATE_WAITING);
    }
    
    /**
     * Returns wheater the thread was left in state error or not
     *
     * @return bool
     */
    public function isTerminated()
    {
        return $this->hasState(self::STATE_ERROR);
    }
    
    /**
     * Waits for the state codition gets a signal
     * 
     * @return void
     */
    public function wait()
    {
        $this->setState(self::STATE_WAITING);
        fhread_cond_wait($this->syncNotify, $this->syncMutex);
    }
    
    /**
     * Notifiy the state cond for waiters to stop waiting
     * 
     * @return void
     */
    public function notify()
    {
        fhread_cond_broadcast($this->syncNotify);
        $this->setState(self::STATE_RUNNING);
    }

    /**
     * Locks thread object's mutex
     *
     * @return void
     */
    public function lock()
    {
        fhread_mutex_lock($this->mutex);
    }

    /**
     * Unlocks thread object's mutex
     *
     * @return void
     */
    public function unlock()
    {
        fhread_mutex_unlock($this->mutex);
    }

    /**
     * Executes given closure synchronized
     *
     * @param callable $sync
     */
    public function synchronized(\Closure $sync)
    {
        fhread_mutex_lock($this->syncMutex);
        $sync->__invoke($this);
        fhread_mutex_unlock($this->syncMutex);
    }

    /**
     * Returns the objects threads id
     *
     * @return int
     */
    public function getThreadId()
    {
        return $this->threadId;
    }
    
    /**
     * Returns current thread id
     */
    static public function getCurrentThreadId()
    {
        return fhread_self();
    }
}
