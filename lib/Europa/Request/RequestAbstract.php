<?php

namespace Europa\Request;
use ArrayIterator;

/**
 * The main request object.
 * 
 * @category Request
 * @package  Europa
 * @author   Trey Shugart <treshugart@gmail.com>
 * @license  Copyright (c) 2011 Trey Shugart http://europaphp.org/license
 */
abstract class RequestAbstract implements \IteratorAggregate, RequestInterface
{
    /**
     * The request unique id.
     * 
     * @var string
     */
    private $id;
    
    /**
     * Request parameters.
     * 
     * @var array
     */
    private $params = array('controller' => 'index');

    /**
     * Magic alias for setParam().
     * 
     * @see \Europa\Request\RequestAbstract::setParam()
     */
    public function __set($name, $value)
    {
        return $this->setParam($name, $value);
    }
    
    /**
     * Magic alias for getParam().
     * 
     * @see \Europa\Request\RequestAbstract::getParam()
     */
    public function __get($name)
    {
        return $this->getParam($name);
    }
    
    /**
     * Magic alias for hasParam().
     * 
     * @see \Europa\Request\RequestAbstract::hasParam()
     */
    public function __isset($name)
    {
        return $this->hasParam($name);
    }
    
    /**
     * Magic alias for removeParam().
     * 
     * @see \Europa\Request\RequestAbstract::removeParam()
     */
    public function __unset($name)
    {
        return $this->removeParam($name);
    }
    
    /**
     * Sets the specified request parameter.
     * 
     * @param string $name  The name of the parameter.
     * @param mixed  $value The value of the parameter.
     * 
     * @return mixed
     */
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
        return $this;
    }
    
    /**
     * Returns the specified request parameter.
     * 
     * @param string $name The name of the parameter.
     * 
     * @return mixed
     */
    public function getParam($name)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }
        return null;
    }
    
    /**
     * Checks for the specified parameter.
     * 
     * @param string $name The parameter to check for.
     * 
     * @return \Europa\Request
     */
    public function hasParam($name)
    {
        return isset($this->params[$name]);
    }
    
    /**
     * Unsets the specified parameter.
     * 
     * @param string $name The parameter to unset.
     * 
     * @return \Europa\Request
     */
    public function removeParam($name)
    {
        if (isset($this->params[$name])) {
            unset($this->params[$name]);
        }
        return $this;
    }
    
    /**
     * Bulk sets parameters.
     * 
     * @param mixed $params The params to set.
     * 
     * @return \Europa\Request
     */
    public function setParams($params)
    {
        if (is_array($params) || is_object($params)) {
            foreach ($params as $name => $value) {
                $this->__set($name, $value);
            }
        }
        return $this;
    }
    
    /**
     * Returns the bound parameters.
     * 
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }
    
    /**
     * Clears request parameters.
     * 
     * @return \Europa\Request
     */
    public function removeParams()
    {
        $this->params = array();
        return $this;
    }
    
    /**
     * Returns the parameters whose names match the regex. The delimiter is automated so you don't have to type it
     * every time you search. The default delimiter is a forward slash.
     * 
     * @param string $pattern   The pattern to use for searching.
     * @param string $delimiter The delimiter to use for the pattern.
     * 
     * @return array
     */
    public function searchParams($pattern, $delimiter = '/', $flags = null)
    {
        $params  = array();
        $pattern = $delimiter . $pattern . $delimiter . $flags;
        foreach ($this->params as $name => $value) {
            if (preg_match($pattern, $name)) {
                $params[$name] = $value;
            }
        }
        return $params;
    }
    
    /**
     * Returns the unique request id of the current request. This is useful for debugging separate logs and probably
     * many other things.
     * 
     * @return string
     */
    public function getId()
    {
        if (!$this->id) {
            $this->id = md5(uniqid(rand(), true));
        }
        return $this->id;
    }
    
    /**
     * Returns an iterator of the parameters.
     * 
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->params);
    }
    
    /**
     * Serializes the request.
     * 
     * @return string
     */
    public function serialize()
    {
        return serialize($this->getParams());
    }
    
    /**
     * Unserializes the request.
     * 
     * @param string $serialized The serialized string.
     * 
     * @return \Europa\Request
     */
    public function unserialize($serialized)
    {
        return $this->setParams(unserialize($serialized));
    }
    
    /**
     * Returns whether or not the request is a CLI request or not.
     * 
     * @return bool
     */
    public static function isCli()
    {
        return PHP_SAPI === 'cli';
    }
    
    /**
     * Whether or not the current request is HTTP or not.
     * 
     * @return bool
     */
    public static function isHttp()
    {
        return isset($_SERVER) && isset($_SERVER['REQUEST_URI']);
    }
}
