# Manifold Laravel Demo App

This demo app shows how to plug a resrouce you have provisioned to inside your [Manifold Dashboard](https://dashboard.manifold.co) can be used easily inside your laravel app using our [manifold-laravel php package](https://packagist.org/packages/manifoldco/manifold-laravel)

This app does nothing special, in fact, most of it is boilerplate and comes from [this](https://laracasts.com/series/laravel-from-scratch-2017/episodes/17) great intro laracast on Laravel Auth and Config set up, __except__ that is pulls are its configuration variables directly from your Manifold project (vs editing your .env file and keeping it up to date).

## Steps to make this app

### Get Composer (if you don't already have)

Run:
```php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php --install-dir=/usr/local/bin
php -r "unlink('composer-setup.php');"
```

### Set up basic demo app

`mkdir demo-app`
`composer create-project laravel/laravel demo-app`
`cd demo-app`
Verify its up and running:

`php artisan serve`

You should see the boilerplate Laravel 5 home page, feel free to shut the serve down `CTL+c`

### Add the Manifold-laravel package:
- Install and publish the configs
```
composer require manifoldco/manifold-laravel
php artisan vendor:publish
```
select `manifoldco\manifold-laravel` from the vendor list

- Set up your API Token in your .env

```
MANIFOLD_API_TOKEN=
MANIFOLD_PROJECT_ID=
```

### Setup the Login in / Auth

`php artisan make:auth`

This adds a bunch of views, routes, etc for account creation, password resets, etc etc.

Before we go and make the migrations, lets plug in a db from Manifold's marketplace, Jawsdb

### Steps to plug in a manifold resource

*go provision a jawsdb, for example*

#### Plugin Jaws

Mofidy `config/manifold.php` and then alias in the mysql config needed.


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

### Mod AppServiceProvider to work with Jawsdb

In the AppServiceProvider.php,you include this code top of the file.

`use Illuminate\Support\Facades\Schema;`

And you add this code in boot method.

`Schema::defaultStringLength(191);`

### Migrate

`php artisan migrate`

### Turn it all on and play

`php artisan serve`