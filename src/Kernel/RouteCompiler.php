<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel;

class RouteCompiler
{
    public static function compile(string $route): string
    {
        $compiled = '';
        $pattern = preg_replace('~\{(\w+)\}~', '(?P<$1>[^/]+)', $route);
        $compiled = '~^' . $pattern . '$~';
        return $compiled;
    }

    /**
     * Will search if URI is set in routes array.
     * Will return null if no route found
     * Will return array with : [
     * 'routeName'=>the existing route,
     * 'id' => id found in the url if exists
     * 'otherId'=> if found....
     * ]
     *
     * @param string $uri
     * @param array $routes
     * @return array|null
     */
    public static function findRoute(string $uri, array $routes): ?array
    {
        foreach($routes as $route=>$_){
            $pattern = self::compile($route);
            if(preg_match($pattern, $uri, $matches)) {
                $params = array_filter(
                    $matches,
                    fn($key)=> is_string($key),
                    ARRAY_FILTER_USE_KEY
                );
                return [
                    'routeName' =>$route,
                    ...$params
                ];
            }
        }
        return null;
    }
}
