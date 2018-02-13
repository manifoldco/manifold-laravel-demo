<?php

return [
    'token' => env('MANIFOLD_API_TOKEN', null),
    'resource_id' => env('MANIFOLD_RESOURCE_ID', null),
    'project' => env('MANIFOLD_PROJECT', null),
    'product_id' => env('MANIFOLD_PRODUCT_ID', null),
    'aliases' => [
        'database' => [
            'connections' => [
                'mysql' => [
                    'host' => (function(){
                        $url = parse_url(config('laravel-demo-mysql.JAWSDB_URL'));
                        return array_key_exists('host', $url) ? $url['host'] : null;
                    })(),
                    'password' => (function(){
                        $url = parse_url(config('laravel-demo-mysql.JAWSDB_URL'));
                        return array_key_exists('pass', $url) ? $url['pass']: null;
                    })(),
                    'username' => (function(){
                        $url = parse_url(config('laravel-demo-mysql.JAWSDB_URL'));
                        return array_key_exists('user', $url) ? $url['user'] : null;
                    })(),
                    'database' => (function(){
                        $url = parse_url(config('laravel-demo-mysql.JAWSDB_URL'));
                        return array_key_exists('path', $url) ? substr($url["path"], 1) : null;
                    })(),
                ]
            ]
        ]
    ],
];
