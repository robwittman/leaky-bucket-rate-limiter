<?php

require_once('vendor/autoload.php');
require_once('../vendor/autoload.php');

use LeakyBucketRateLimiter\RateLimiter;

$slim = new Slim\App();

$slim->get('/', function($request, $response) {
    return $response->withJson([
        'message' => "Hello World"
    ]);
});

$slim->get('/limit', function($request, $response) {
    return $response->withJson([
        'header' => $response->getHeader("X-Rate-Limit")[0]
    ]);
});

$slim->get('/unlimited', function($request, $response) {
    return $response->withJson([
        'route' => $request->getUri()->getPath()
    ]);
});

$slim->add(new RateLimiter([
    'callback' => function($request) {
        return [
            'token' => '172.17.0.1'
        ];
    },
    'throttle' => function($response, $bucket, $settings) {
        return  $response
                    ->withStatus(429)
                    ->withJson([
                        'message' => "User request limit reached"
                    ]);
    },
    'ignore' => [
        '/unlimited'
    ]
],[
    'scheme' => 'tcp',
    'host' => 'redis',
    'port' => 6379
]));

$slim->run();
