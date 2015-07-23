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
 * Condition object implementation
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/fhreads
 * @link      http://www.appserver.io
 */
class Cond
{
    /**
     * Broadcast to all Threads blocking on a call to Cond::wait().
     *
     * @param int $condition A handle to a Condition Variable returned by a previous call to Cond::create()
     *
     * @link http://www.php.net/manual/en/cond.broadcast.php
     * @return bool A boolean indication of success
     */
    public static function broadcast($condition)
    {
        if (fhread_cond_broadcast($condition) === 0) {
            return true;
        }
        return false;
    }
    
    /**
     * Creates a new Condition Variable for the caller.
     *
     * @link http://www.php.net/manual/en/cond.create.php
     * @return int A handle to a Condition Variable
     */
    public static function create()
    {
        return fhread_cond_init();
    }
    
    /**
     * Destroy a condition
     *
     * Destroying Condition Variable handles must be carried out explicitly by the programmer when they are
     * finished with the Condition Variable.
     * No Threads should be blocking on a call to Cond::wait() when the call to Cond::destroy() takes place.
     *
     * @param int $condition A handle to a Condition Variable returned by a previous call to Cond::create()
     *
     * @link http://www.php.net/manual/en/cond.destroy.php
     * @return bool A boolean indication of success
     */
    public static function destroy($condition)
    {
        if (fhread_cond_destroy($condition) === 0) {
            return true;
        }
        return false;
    }
    
    /**
     * Signal a Condition
     *
     * @param int $condition A handle to a Condition Variable returned by a previous call to Cond::create()
     *
     * @link http://www.php.net/manual/en/cond.signal.php
     * @return bool A boolean indication of success
     */
    public static function signal($condition)
    {
        if (fhread_cond_signal($condition) === 0) {
            return true;
        }
        return false;
    }
    
    /**
     * Wait for a signal on a Condition Variable, optionally specifying a timeout to limit waiting time.
     *
     * @param int $condition A handle to a Condition Variable returned by a previous call to Cond::create()
     * @param int $mutex A handle returned by a previous call to Mutex::create() and owned (locked) by the caller.
     * @param int $timeout An optional timeout, in microseconds
     *
     * @return bool A boolean indication of success
     */
    public static function wait($condition, $mutex, $timeout = null)
    {
        if (fhread_cond_wait($condition, $mutex) === 0) {
            return true;
        }
        return false;
    }
}
