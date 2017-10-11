<?php

/**
 * Created by PhpStorm.
 * User: Flu
 * Date: 10/10/2017
 * Time: 5:40 PM
 */
class Router
{
    private $routes = array(
        'get'  => array(),
        'post' => array()
    );

    public function get ($uri, $callback) {
        $this->add('get', $uri, $callback);
    }

    public function post ($uri, $callback) {
        $this->add('post', $uri, $callback);
    }

    public function both ($uri, $callback) {
        $this->add('both', $uri, $callback);
    }

    private function uriExplode ($uri) {
        $uri = trim($uri);
        $uri = explode('?', $uri);
        $uri_array = explode('/', $uri[0]);
        $uri_array = array_filter($uri_array);

        return $uri_array;
    }

    private function add ($type, $uri, $callback) {
        if (is_callable($callback)) {
            $uri_array = $this->uriExplode($uri);

            $uri_count = count($uri_array);

            $route = array(
                'uri'      => $uri_array,
                'count'    => $uri_count,
                'callback' => $callback
            );

            if ($type != 'both') {
                array_push($this->routes['get'], $route);
                array_push($this->routes['post'], $route);
            } else {
                array_push($this->routes[$type], $route);
            }

        }
    }

    public function route () {
        $http_method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];

        if (isset($this->route[$http_method]) && $this->route[$http_method]) {
            $uri_array = $this->uriExplode($uri);

            $uri_count = count($uri_array);

            $params = array();
            $route_match =  true;

            foreach ($this->routes[$http_method] as $route) {
                if ($route == $uri_count) {
                    foreach ($route['uri'] as $k => $u) {
                        if ($uri_array[$k] != $u && (substr($u, 0, 1) != '{' && substr($u, -1) != '}')) {
                            $route_match = false;
                            break;
                        }

                        if (substr($u, 0, 1) == '{' && substr($u, -1) == '}') {
                            array_push($params, $uri_array[$k]);
                        }
                    }
                }

                if ($route_match) {
                    call_user_func_array($route['callback'], $params);
                    return;
                }
            }

            return;
        }

        return false;
    }
}