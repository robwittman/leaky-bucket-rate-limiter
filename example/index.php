<?php

require_once('vendor/autoload.php');

use LeakyBucketRateLimiter\LeakyBucket;

$bucket = new LeakyBucket([
    'callback' => function($response) {
        error_log("Callback called");
    }
]);
