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
                    'host' => function(){
                        $url = parse_url(config('laravel-demo-mysql.JAWSDB_URL'));
                        return $url['host'];
                    },
                    'password' => function(){
                        $url = parse_url(config('laravel-demo-mysql.JAWSDB_URL'));
                        return $url['pass'];
                    },
                    'username' => function(){
                        $url = parse_url(config('laravel-demo-mysql.JAWSDB_URL'));
                        return $url['user'];
                    },
                    'database' => function(){
                        $url = parse_url(config('laravel-demo-mysql.JAWSDB_URL'));
                        return substr($url["path"], 1);
                    }
                ]
            ]
        ]
    ],
];
