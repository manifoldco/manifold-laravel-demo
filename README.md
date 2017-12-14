# Manifold Laravel Demo App
[Homepage](https://manifold.co) |
[Twitter](https://twitter.com/manifoldco) |
[Code of Conduct](./.github/CODE_OF_CONDUCT.md) |
[Contribution Guidelines](./.github/CONTRIBUTING.md)

[![Build Status](https://travis-ci.org/manifoldco/manifold-laravel-demo.svg?branch=master)](https://travis-ci.org/manifoldco/manifold-laravel-demo)
[![License](https://img.shields.io/badge/license-BSD-blue.svg)](./LICENSE)

![Laravel Manifold](./banner.png)

[Manifold](https://www.manifold.co) gives you a single account to purchase and manage cloud services from multiple providers, giving you managed logging, email, MySQL, Postgres, Memcache, Redis, and more. Manifold also lets you register configurations for your services external to Manifold's marketplace, giving you a single location to organize and manage the credentials for your environments.

This demo app shows how to plug resources you have provisioned in your [Manifold Dashboard](https://dashboard.manifold.co) into a laravel application using our [manifold-laravel php package](https://packagist.org/packages/manifoldco/manifold-laravel).  Most of the demo is boilerplate and comes from [this](https://laracasts.com/series/laravel-from-scratch-2017/episodes/17), a great intro laracast on Laravel Auth, __except__ that it pulls its configuration variables directly from your Manifold project (vs editing your .env file and keeping it up to date).

For this demo we focus on two Providers' services available on our platform, JawsDB and LogDNA, but the same methods can be used for any of the services we provide, as well as custom resources you have added yourself.

At the end, you'll have one API key, a project name, and thats it, the rest gets pulled into your app via our package.

*If you would like a longer tutorial on using this demo application, please see our blog post [here](https://blog.manifold.co/announcing-manifolds-laravel-integration-12b9b0389579).*

### Prerequisites:
- You have a Laravel application (feel free to clone this repo and use it).
- You have a [Manifold account](https://dashboard.manifold.co) (free!)
- You are comfortable provisioning resources in Manifold and have provisioned either JawsDB or LogDNA (guide [here](https://docs.manifold.co/docs/quickstart-guide-6G2IZEjhD20oK6iISoQOE6))

## Add the manifold-laravel package:

Install and publish the configurations
```
$ composer require manifoldco/manifold-laravel
$ php artisan vendor:publish
```
Select `manifoldco\manifold-laravel` from the vendor list.

This will generate `config/manifold.php` and add two lines to your `.env` file:
```
MANIFOLD_API_TOKEN=
MANIFOLD_PROJECT=
```
You can leave those blank for now, we will modify them after.

## Set up your API access Token
You will need an API Token so your Laravel application can access Manifold.

1. Download the [Manifold CLI](https://docs.manifold.co/docs/install-77T6auwaaIQcgA4QGouO0)
2. Login using: `manifold login`
3. Create an API token, giving it read credentials:
```
$ manifold tokens create
✔ Token Description: test
Use the arrow keys to navigate: ↓ ↑ → ←
? Select Role:
    read
  ▸ read-credentials
    write
    admin
```
4. Edit your `.env` file (never to be committed to git) with:
```
MANIFOLD_API_TOKEN=<API TOKEN KEY>
MANIFOLD_PROJECT=project-name
```
Note on security: If you are using a CI tool like [travis-ci](https://travis-ci.com) you should consider injecting these values into your build environment securely, see the post Patrick wrote [here](https://blog.manifold.co/manifold-travis-ci-manage-your-secrets-without-the-hassle-a13c01e12014) for instructions on how.

## Logging with LogDNA

Like all good applications, we want our logs pushed to a cloud logging provider. For the demo we've [LogDNA](https://www.manifold.co/services/logdna#pricing). You will need the name of your LogDNA resource that was provisioned in the previous step. We named ours `logdna`.

In your code:

1. Add the LogDNA monolog package:
```
composer require nvanheuverzwijn/monolog-logdna
```
2. Modify `bootstrap/app.php` to extend the default logger:
```
$app->configureMonologUsing(function($monolog) {
    $handler = new \Zwijn\Monolog\Handler\LogdnaHandler(config('logdna.KEY'),config('manifold.project'),\Monolog\Logger::DEBUG);
    $monolog->pushHandler($handler);
});
```
Explanation: We are pulling in the configuration (`KEY`) for the resource `logdna` via the manifold package `config('logdna.KEY')` . If you had called your logdna resource logger, or anything else, swap `logdna` for the name you gave it.  Also, we are using the name of the project to sent to LogDNA, you could put your own app name as the second argument.

Thats it, logging hooked up!

We suggest checking out Laravel's docs [on logging](https://laravel.com/docs/5.5/errors) to get the most out of logging from your App.


## Database with JawsDB MySQL

JawsDB is a great example of how the manifold-laravel package supports aliasing.  JawsDB returns its entire username, password, hostname, database and port in one long URL.  This is handy, but not so much for Laravel as we need those broken out into different parts. So unlike LogDNA where we could simply reference the secrets by `resource-name.key`, we need to do some fun aliasing in the `config/manifold.php` file.

In your code:

Modify `config/manifold.php` and then alias in the mysql configuration as needed.

```
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

```


We are setting up a series of alias' to set the database related configuration. And that's it! No need to modify the `config/database.php` file any more than simply telling it to use MySQL.

One last step to get it all working:

#### Mod AppServiceProvider to work with JawsDB
In the AppServiceProvider.php, include this code at the top of the file.
`use Illuminate\Support\Facades\Schema;`

And add this code in boot method.
`Schema::defaultStringLength(191);`

### Run the migrations

`php artisan migrate`

#  Turn it all on and play

`php artisan serve`

You should now see a fancy default laravel app, with login and register functionality all in place.  Under the hood it is using your JawsDB mysql instance, and LogDNA to serve up its internal logs.

From here, you could plug in [MemCache](https://www.manifold.co/services/memcachier-cache) for caching (or [Redis](https://www.manifold.co/services/redisgreen) ) or even switch over to a different JawsDB offering ([Postgres](https://www.manifold.co/services/jawsdb-postgres)). Heck, want to plug elastic in, or email, the list goes on and on :-)
