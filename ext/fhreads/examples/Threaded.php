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
 * Threaded object implementation
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/fhreads
 * @link      http://www.appserver.io
 */
class Threaded implements ArrayAccess, Countable
{
    
    /**
     * Assigns a value to the specified offset
     *
     * @param string The offset to assign the value to
     * @param mixed  The value to set
     * @access public
     * @abstracting ArrayAccess
     */
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $offset = 0;
            while(in_array("$offset", array_keys((array)$this))) { ++$offset; }
        }
        $this->{"$offset"} = $value;
    }
    
    /**
     * Whether or not an offset exists
     *
     * @param string An offset to check for
     * @access public
     * @return boolean
     * @abstracting ArrayAccess
     */
    public function offsetExists($offset) {
        return isset($this->{"$offset"});
    }
    
    /**
     * Unsets an offset
     *
     * @param string The offset to unset
     * @access public
     * @abstracting ArrayAccess
     */
    public function offsetUnset($offset) {
        if ($this->offsetExists($offset)) {
            unset($this->{"$offset"});
        }
    }
    
    /**
     * Returns the value at specified offset
     *
     * @param string The offset to retrieve
     * @access public
     * @return mixed
     * @abstracting ArrayAccess
     */
    public function offsetGet($offset) {
        return $this->offsetExists($offset) ? $this->{"$offset"} : null;
    }
    
    /**
     * Fetches a chunk of the objects properties table of the given size
     *
     * @param int $size The number of items to fetch
     *
     * @link http://www.php.net/manual/en/threaded.chunk.php
     * @return array An array of items from the objects member table
     */
    //public function chunk($size) {}

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count((array)$this);
    }

    /**
     * Retrieves terminal error information from the referenced object
     *
     * @link http://www.php.net/manual/en/threaded.getterminationinfo.php
     * @return array|bool array containing the termination conditions of the referenced object
     */
    //public function getTerminationInfo() {}

    /**
     * Tell if the referenced object is executing
     *
     * @link http://www.php.net/manual/en/threaded.isrunning.php
     * @return bool A boolean indication of state
     */
    //public function isRunning() {}

    /**
     * Tell if the referenced object exited, suffered fatal errors, or threw uncaught exceptions during execution
     *
     * @link http://www.php.net/manual/en/threaded.isterminated.php
     * @return bool A boolean indication of state
     */
    //public function isTerminated() {}

    /**
     * Tell if the referenced object is waiting for notification
     *
     * @link http://www.php.net/manual/en/threaded.iswaiting.php
     * @return bool A boolean indication of state
     */
    //public function isWaiting() {}

    /**
     * Lock the referenced objects property table
     *
     * @link http://www.php.net/manual/en/threaded.lock.php
     * @return bool A boolean indication of state
     */
    //public function lock() {}

    /**
     * Merges data into the current object
     *
     * @param mixed $from The data to merge
     * @param bool $overwrite Overwrite existing keys flag, by default true
     *
     * @link http://www.php.net/manual/en/threaded.merge.php
     * @return bool A boolean indication of success
     */
    public function merge($from, $overwrite = true)
    {
        foreach($from as $fromKey => $fromValue) {
            if ((isset($this->{$fromKey}))&&($overwrite === false)) {
                continue;
            }
            $this->{$fromKey} = $fromValue;
        }
    }
    
    public function next() {}
    public function current() {}
    public function key() {}
    public function valid() {}
    public function rewind() {}

    /**
     * Send notification to the referenced object
     *
     * @link http://www.php.net/manual/en/threaded.notify.php
     * @return bool A boolean indication of success
     */
    //public function notify() {}

    /**
     * Pops an item from the objects property table
     *
     * @link http://www.php.net/manual/en/threaded.pop.php
     * @return mixed The last item from the objects properties table
     */
    //public function pop() {}

    /**
     * The programmer should always implement the run method for objects that are intended for execution.
     *
     * @link http://www.php.net/manual/en/threaded.run.php
     * @return void The methods return value, if used, will be ignored
     */
    //public function run() {}

    /**
     * Shifts an item from the objects properties table
     *
     * @link http://www.php.net/manual/en/threaded.shift.php
     * @return mixed The first item from the objects properties table
     */
    //public function shift() {}

    /**
     * Executes the block while retaining the synchronization lock for the current context.
     *
     * @param \Closure $function The block of code to execute
     * @param mixed $args... Variable length list of arguments to use as function arguments to the block
     *
     * @link http://www.php.net/manual/en/threaded.synchronized.php
     * @return mixed The return value from the block
     */
    //public function synchronized(\Closure $function, $args = null) {}

    /**
     * Unlock the referenced objects storage for the calling context
     *
     * @link http://www.php.net/manual/en/threaded.unlock.php
     * @return bool A boolean indication of success
     */
    //public function unlock() {}

    /**
     * Waits for notification from the Stackable
     *
     * @param int $timeout An optional timeout in microseconds
     *
     * @link http://www.php.net/manual/en/threaded.wait.php
     * @return bool A boolean indication of success
     */
    //public function wait($timeout) {}
}
