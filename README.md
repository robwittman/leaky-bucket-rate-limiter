### PSR-7 Leaky Bucket Rate Limiter


This middleware enables API Rate-Limiting based on a Leaky Bucket algorithm.

### Usage

To get started, you can easily use composer:

`composer require robwittman/leaky-bucket-rate-limiter`

Once installed, require the package, apply some settings, and start limiting.

```php
<?php

require_once('vendor/autoload.php');

use LeakyBucketRateLimiter\RateLimiter;

$slim = \Slim\App();

$slim->add(new RateLimiter([
    'callback' => function(RequestInterface $request) {
        return [
            'token' => <token>
        ];
    },
    'throttle' => function(ResponseInterface $response) {
        return $response->withStatus(429)->withJson([
            'error' => "User request limit reached"
        ]);
    }
]))

$slim->run();
```

The only required settings to use RateLimiter is a callback and throttle.

#### Examples

##### IP Address
```php
$slim->add(new RateLimiter([
    'callback' => function(RequestInterface $request) {
        return [
            'token' => $_SERVER['REMOTE_ADDR']
        ];
    }
]));
```

##### Session ID
```php
$slim->add(new RateLimiter([
    'callback' => function(RequestInterface $request) {
        return [
            'token' => session_id()
        ];
    }
]));
```

##### Request Attribute
``` php
$slim->add(new RateLimiter([
    'callback' => function(RequestInterface $request) {
        return [
            'token' => $request->getAttribute('<token_or_uid>')
        ];
    },
]));

```

Once the bucket has a token to act on, it communicates with Redis to keep track of traffic. If the token is over it's request limit, it will trigger the `throttle` function passed to the constructor.

### Parameters

#### Callback *(required)*

The callback argument is called when the Limiter needs a key to check. It passes along the Request object, and can either return an array with a (string) 'token' key, or can return TRUE to skip rate limiting
``` php
$slim->add(new RateLimiter([
    'callback' => function(RequestInterface $request) {
        return [
            'token' => session_id()
        ];
    }
]))
```

#### Throttle *(required)*

Tell the Limiter how to respond to throttled requests
``` php
$slim->add(new RateLimiter([
    'throttle' => function(ResponseInterface $response) {
        return $response->withStatus(429)->withJson([
            'message' => "Dude, you gotta slow down"
        ]);
    };
]));
```

**NOTE** All further settings assume `callback` and `throttle` parameters are already set

#### Capacity and Leak

Capacity is the total amount of drips (requests) the bucket may contain. Leak is the amount of drips per second that you want to remove from the bucket
```php
$slim->add(new RateLimiter([
    'capacity' => 45,
    'leak' => 1
]));
```


### Ignored routes

You can pass an array of routes that you do not want to rate limit. This completely bypasses the rate limit middleware, so they will not have respective headers either
``` php
$slim->add(new RateLimiter([
    'ignore' => [
        'auth/token',
        'users/me',
        'other/ignored/routes'
    ]
]));
```

#### Prefix / Postfix

Provide a prefix / suffix for the bucket key. The key will be stored in Redis as `PREFIX.key.SUFFIX`
``` php
$slim->add(new RateLimiter([
    'prefix' => 'bucket-o-leaks',
    'suffix' => "limiter"
]));
```

#### Header
Specify what header to provide, containing Rate Limiting info. Set to false to disable.
```php
$slim->add(new RateLimiter([
    'header' => "Rate-Limiting-Meta"
]));

// Rate-Limiting-Meta: X / Y
// X = Current drips in bucket, Y = capacity
```


### Storage

By default, the Rate Limiter will attempt to connect to a local redis instance at http://127.0.0.1:6379, as per `Predis\Client()`.This can be overridden by providing either an array of settings for `Predis\Client` to connect with,
or providing an object with methods get() and set() for storing and retrieving data (mysql, memcached, mongo, etc). If using docker-compose development container, just use `redis` as the hostname, and container linking will connect it.

``` php
$slim->add(new RateLimiter([
    // Rate limiter settings
], [
    'scheme' => 'tcp://',
    'host' => 'redis',
    'port' => 6379
]))

// OR

class ObjectWithGetAndSetMethods {
    public function get($key) {
        return $this->{$key};
    }
    public function set($key, $value) {
        $this->{$key} = $value;
    }
}
$storage = new ObjectWithGetAndSetMethods();
$slim->add(new RateLimiter([
    // Rate limiter settings
], $storage));
```

### Development / Testing

This library comes packaged with a Docker environment for testing and development. If you're not using Docker, you ought to be!

To bootstrap an environment using docker-compose, simply

`docker-compose up`

This generates a PHP container with source code and packages, running a local dev server. It also provisions and links a Redis container to use as your storage mechanism.

If you're not using docker-compose, or want to implement a different storage system, you can launch a solo container.

```
docker build -t <tag-name> .

docker run -v $PWD:/opt -p "8001:8001" <container_name>
```

The server can be accessed at :8001, and contains a mini app to play around with. Running tests is equally as easy, and is centered around docker

```shell
docker-compose up
docker-compose exec web bash
vendor/bin/phpunit
```
