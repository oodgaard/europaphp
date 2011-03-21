<?php

namespace Europa\Router;

/**
 * A request router.
 * 
 * @category Router
 * @package  Europa
 * @author   Trey Shugart <treshugart@gmail.com>
 * @license  Copyright (c) 2011 Trey Shugart http://europaphp.org/license
 */
class Request extends \Europa\Router
{
    /**
     * The request to route.
     * 
     * @var Europa\Request
     */
    private $request;
    
    /**
     * Sets up the request router using the specified request.
     * 
     * @param Europa\Request $request The request to route.
     * 
     * @return \Europa\Router\Request
     */
    public function __construct(\Europa\Request $request)
    {
        $this->request = $request;
    }
    
    /**
     * Dispatches, converts the request to a string and returns it.
     * 
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->dispatch()->__toString();
        } catch (\Exception $e) {
            $e = new Exception($e->getMessage(), $e->getCode());
            $e->trigger();
        }
    }
    
    /**
     * Returns the request to be routed.
     * 
     * @return \Europa\Router\Request
     */
    public function getRequest()
    {
        return $this->request;
    }
    
    /**
     * Dispatches the set request. If $subject is specified, then it overwrites the
     * default Europa\Request->__toString() return value for route matching.
     * 
     * The request that was routed is returned. If the request isn't matched, then
     * false is returned.
     * 
     * @param string $subject The subject being routed.
     * 
     * @return Europa\Request
     */
    public function dispatch($subject = null)
    {
        $request = $this->getRequest();
        $subject = $subject ? $subject : $request->__toString();
        return $request->setParams($this->query($subject))->dispatch();
    }
}