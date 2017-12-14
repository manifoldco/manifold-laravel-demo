# Manifold Laravel Demo App

This demo app shows how to plug resources you have provisioned  [Manifold Dashboard](https://dashboard.manifold.co) in your laravel app using our [manifold-laravel php package](https://packagist.org/packages/manifoldco/manifold-laravel)

This demo app does nothing special, in fact, most of it is boilerplate and comes from [this](https://laracasts.com/series/laravel-from-scratch-2017/episodes/17) great intro laracast on Laravel Auth and configuration set up, __except__ that it pulls its configuration variables directly from your Manifold project (vs editing your .env file and keeping it up to date).

I'll take you through the main three steps to recreate the code in this repo:
1. Setting a demo laravel app
2. Setting up some resources to use inside manifold.co
3. Plugging in these resources to your app

At the end, you'll have one API key, a project name, and thats it, the rest gets pulled into your app via our package.

# Steps to make a simple Laravel app

## Get Composer (if you don't already have it)

Run:
```php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php --install-dir=/usr/local/bin
php -r "unlink('composer-setup.php');"
```

## Set up basic demo app

`mkdir demo-app`
`composer create-project laravel/laravel demo-app`
`cd demo-app`

Verify its up and running:

`php artisan serve`

You should see the boilerplate Laravel 5 home page, feel free to shut the server down `CTL+c`

## Add the Manifold-laravel package:

Install and publish the configurations
```
composer require manifoldco/manifold-laravel
php artisan vendor:publish
```
Select `manifoldco\manifold-laravel` from the vendor list.

This will generate `config/manifold.php` and add two lines to your .env file:
```
MANIFOLD_API_TOKEN=
MANIFOLD_PROJECT=
```
You can leave those blank for now, we will get to modifying that a bit later on.

## Setup the Login in / Auth
For the purpose of the demo, we are simply making an app you can log in and out of, nothing else.

Thankfully laravel makes this really easy to do. Run this:
`php artisan make:auth`

This adds a bunch of views, routes, etc for account creation, password resets, etc.

Before we go and run the migrations, lets plug in a database from Manifold's marketplace, Jawsdb, as well as some other services.


# Adding services from Manifold

## Setting up your Manifold account and first project

1. Create an account (free!) [here](dashboard.manifold.co)
2. Once your account is verified, follow the flow to make your first project [here](https://dashboard.manifold.co/projects/create?owner=self)
3. Feel free to work ahead and add logdna, and jawsdb mysql to your project now, remember what you name the resources (I named mine logdna and jawsdb)

Also note you can do all of the above via our CLI, if thats more your thing.  You can find it [here](https://github.com/manifoldco/manifold-cli) and docs [here](https://docs.manifold.co/docs/install-77T6auwaaIQcgA4QGouO0).  You will need the CLI installed to generate an api token anyway (next step).

### Set up your API access Token

1. Using the CLI [here](https://github.com/manifoldco/manifold-cli), login:
 ```
 manifold login
 ```
2. Create an api access token, giving it `read-credentials`:
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
3. Edit your `.env` file (never to be committed to git) with:
```
MANIFOLD_API_TOKEN=<API TOKEN KEY>
MANIFOLD_PROJECT=project-name
```
Note on security: If you are using a CI tool like [travis-ci](travis-ci.com) you should consider injecting these values into your build environment securely, see this post I wrote [here](https://blog.manifold.co/manifold-travis-ci-manage-your-secrets-without-the-hassle-a13c01e12014) for instructions on how.

## + Logging - Logdna

Like all good apps, I want my logs pushed to a cloud logging provider. For the demo I've chosen [Logdna](https://www.manifold.co/services/logdna#pricing), if you already have it set up, all you need to know is the name you gave your resource (you can see this in the [dashboard](https://dashboard.manifold.co) or via the CLI using `manifold list`).

In your code:

1. Add the logdna monolog package:
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
Explanation: We are pulling in the configuration (`KEY`) for logdna via the manifold package `config('logdna.KEY')` . If you had called your logdna resource logger, or anything else, swap `logdna` for the name you gave it.  Also, I'm passing the name of the project up to logdna for fun, I could have used "my app name" or anything else I chose there.

Thats it, logging hooked up!

Of course, your app will want to have informative and useful logging, to see more details
on Laravel logging, I suggest checking out their docs [here](https://laravel.com/docs/5.5/errors).  With the above done all your logs will pump to your logdna dashboard, which you can SSO into via the manifold dashboard.


## + Database - Jawsdb mysql

Similar to provisioning LogDNA, go and spin up a JawsDB MYSQL resource if you haven't already, without it your app won't turn on (we still need to run those migrations, remember?).

JawsDB is a great example of how manifold supports aliasing.  JawsDB returns its entire username, password, hostname, database and pot in one long db URL.  This is handy, but not so much for Laravel as we need those broken out into different parts. So unlike Logdna where we could simply reference the secrets by `resource-name.key`, we need to do some fun aliasing in the `config/manifold.php` file.

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


A bit of an explanation: I'm using the full `project-name.resourcename.key` reference in the above code to show you it is available to you, but I could have simply done `mysql.JAWSDB_URL` to pull the full secret in.  I'm then setting up a series of alias' to set the database related configuration. And thats it! There is no need to mod the `config/database.php` file any more then simply telling it to use `mysql`.  The configs will now be used where needed and laravel will use our JawsDB instance!

Oops, almost, small DB related issue, specific to JawsDB (well, mysql) and Laravel, to get it all working, do the following: 

#### Mod AppServiceProvider to work with JawsDB
In the AppServiceProvider.php, include this code at the top of the file.
`use Illuminate\Support\Facades\Schema;`

And add this code in boot method.
`Schema::defaultStringLength(191);`

## Run the migrations

`php artisan migrate`

#  Turn it all on and play

`php artisan serve`

You should now see a fancy default laravel app, with login and register functionality all in place.  Under the hood it is using your JawsDB mysql instance, and LogDNA to serve up its internal logs.

From here, we could plug in [MemCache](https://www.manifold.co/services/memcachier-cache) for caching (or [Redis](https://www.manifold.co/services/redisgreen) ) or even switch over to a different JawsDB offering ([Postgres](https://www.manifold.co/services/jawsdb-postgres)). Heck, want to plug elastic in, or email, the list goes on and on :-)

Let me know your thoughts? Have a service, even one not in Manifold you want to plug into your Laravel app?  Ping me and maybe we can help (we support custom resources).

-Patrick
