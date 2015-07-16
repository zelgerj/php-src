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
 * Mutex object implementation
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/fhreads
 * @link      http://www.appserver.io
 */
class Mutex
{
    /**
     * Create, and optionally lock a new Mutex for the caller
     *
     * @param bool $lock Setting lock to true will lock the Mutex for the caller before returning the handle
     *
     * @link http://www.php.net/manual/en/mutex.create.php
     * @return int A newly created and optionally locked Mutex handle
     */
    public static function create($lock = false)
    {
        return fhread_mutex_init();
    }
    
    /**
     * Destroy mutex
     *
     * Destroying Mutex handles must be carried out explicitly by the programmer when they are
     * finished with the Mutex handle.
     *
     * @param int $mutex A handle returned by a previous call to Mutex::create().
     *
     * @link http://www.php.net/manual/en/mutex.destroy.php
     * @return bool A boolean indication of success
     */
     public static function destroy($mutex)
     {
         if (fhread_mutex_destroy($mutex) === 0) {
             return true;
         }
         return false;
     }
     
    /**
     * Attempt to lock the Mutex for the caller.
     *
     * An attempt to lock a Mutex owned (locked) by another Thread will result in blocking.
     *
     * @param int $mutex A handle returned by a previous call to Mutex::create().
     *
     * @link http://www.php.net/manual/en/mutex.lock.php
     * @return bool A boolean indication of success
     */
    public static function lock($mutex)
    {
        if (fhread_mutex_lock($mutex) === 0) {
            return true;
        }
        return false;
    }
    
    /**
     * Attempt to lock the Mutex for the caller without blocking if the Mutex is owned (locked) by another Thread.
     *
     * @param int $mutex A handle returned by a previous call to Mutex::create().
     *
     * @link http://www.php.net/manual/en/mutex.trylock.php
     * @return bool A boolean indication of success
     */
    public static function trylock($mutex)
    {
        if (fhread_mutex_trylock($mutex) === 0) {
            return true;
        }
        return false;
    }
    
    /**
     * Release mutex
     *
     * Attempts to unlock the Mutex for the caller, optionally destroying the Mutex handle.
     * The calling thread should own the Mutex at the time of the call.
     *
     * @param int $mutex A handle returned by a previous call to Mutex::create().
     * @param bool $destroy When true pthreads will destroy the Mutex after a successful unlock.
     *
     * @link http://www.php.net/manual/en/mutex.unlock.php
     * @return bool A boolean indication of success
     */
    public static function unlock($mutex, $destroy = false)
    {
        if (fhread_mutex_unlock($mutex) === 0) {
            return true;
        }
        return false;
    }
}