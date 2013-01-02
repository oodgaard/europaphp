<?php

namespace Europa\Router;
use Europa\Config\Config;
use Europa\Exception\Exception;
use Europa\Filter\ClassNameFilter;
use Europa\Request\CliInterface;
use Europa\Request\HttpInterface;
use Europa\Request\RequestInterface;

class Route
{
    const CONTROLLER = 'controller';

    private $config = [
        'match'             => '^$',
        'method'            => 'get',
        'format'            => ':controller/:action',
        'params'            => ['controller' => 'index', 'action' => 'get'],
        'controller.prefix' => 'Controller\\',
        'controller.suffix' => ''
    ];

    public function __construct($config)
    {
        $this->config = new Config($this->config, $config);

        if (!$this->config->controller) {
            Exception::toss('The route "%s" did not provide a controller class name.', $this->config->expression);
        }
    }

    public function __invoke($name, RequestInterface $request)
    {
        // Guilty until proven innocent.
        $matches = false;

        // Allow both HTTP and CLI requests to be routed.
        if ($request instanceof HttpInterface) {
            $matches = $this->handleHttpRequest($request);
        } elseif ($request instanceof CliInterface) {
            $matches = $this->handleCliRequest($request);
        }

        // If nothing was matched, the route failed.
        if (!$matches) {
            return false;
        }

        // The first match is the whole request; we don't use this.
        array_shift($matches);

        // Set defaults and matches from the route expression.
        $request->setParams($this->config->params);
        $request->setParams($matches);

        // A specified controller class overrides the "controller" parameter in the request.
        $controller = $this->resolveController($request);
        
        // Ensure the controller exists.
        if (!class_exists($controller)) {
            Exception::toss('The controller class "%s" given for route "%s" does not exist.', $controller, $name);
        }

        return new $controller;
    }

    public function format(array $params = [])
    {
        $uri    = $this->config->format;
        $params = array_merge($this->config->defaults->export(), $params);

        foreach ($params as $name => $value) {
            $uri = str_replace(':' . $name, $value);
        }

        return $uri;
    }

    private function handleHttpRequest(HttpInterface $request)
    {
        if ($this->config->method !== $request->getMethod()) {
            return false;
        }

        if (!preg_match('!' . $this->config->match . '!', $request->getUri()->getRequest(), $matches)) {
            return false;
        }

        return $matches;
    }

    private function handleCliRequest(CliInterface $request)
    {
        if (!$this->config->match) {
            return false;
        }

        if ($this->config->method !== 'cli') {
            return false;
        }

        if (!preg_match('!' . $this->config->match . '!', $request->getCommand(), $matches)) {
            return false;
        }

        return $matches;
    }

    private function resolveController(RequestInterface $request)
    {
        return (new ClassNameFilter($this->config->controller))->__invoke($request->getParam(self::CONTROLLER));
    }
}