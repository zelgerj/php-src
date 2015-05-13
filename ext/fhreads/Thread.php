<?php

interface Runnable
{
    public function run();
}

abstract class Thread implements Runnable
{
    protected $threadId = null;
    
    abstract function run();
    
    public function getGlobalsIdentifier()
    {
        return get_class($this) . "#" . fhread_object_get_handle($this);
    }
    
    public function start()
    {
        // set ref to globals for thread to use it from
        $GLOBALS[$this->getGlobalsIdentifier()] = &$this;
        
        echo $this->getGlobalsIdentifier() . PHP_EOL;
        
        // create, start thread and save thread id 
        $this->threadId = fhread_create($this->getGlobalsIdentifier());
    }
        
    public function getThreadId()
    {
        return $this->threadId;
    }
    
    public function join()
    {
        if ($this->getThreadId()) {
            fhread_join($this->getThreadId());
        }
    }
    
    public function detach()
    {
        unset($GLOBALS[$this->getGlobalsIdentifier()]);
    }
}
