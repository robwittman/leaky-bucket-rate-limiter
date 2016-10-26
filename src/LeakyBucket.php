<?php

/**
 *
 */

namespace LeakyBucketRateLimiter;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class LeakyBucket {

    /**
     * String added to beginning of bucket key
     * @var string
     */
    const PREFIX = 'rate_limiter:';

    /**
     * String added to end of key
     * @var string
     */
    const POSTFIX = ':bucket';

    /**
     * Sensible defaults
     * @var array
     */
    private static $defaults = array(
        'capacity' => 20,
        'leak'     => 1,
        'callback' => null,
        'ignore'   => [],
        'header'   => 'X-Rate-Limit'
    );

    /**
     * Settings for the bucket
     * @var array
     */
    private $settings = array();

    /**
     * Storage mechanism for rate tracking
     *
     * This will default to a tcp://localhost:6379 (Redis)
     *
     * @var mixed
     */
    private $storage = [];

    /**
     * Create a new Rate Limiter
     *
     * @param array $settings
     * @param mixed $storage   Mechanism for storing and retrieving rate data
     */
    public function __construct(array $settings = array(), $storage = null) {

        $settings       = array_intersect_key($settings, self::$defaults);
        $this->settings = array_merge(self::$defaults, $settings);

        if(is_null($this->settings['callback'])) { throw new \InvalidArgumentException("Callback required for Rate Limiter"); }
        // Set up our storage mechanism
    }

    /**
     * Execute our Rate Limiter
     * @param  \Psr\Http\Message\RequestInterface  $request
     * @param  \Psr\Http\Message\ResponseInterface $response
     * @param  callable          $next
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next) {
        // First things first, let's load our object and try and find the data point that we need to
        // rate limit on. This can be a token, IP address, whatever. The callable must return an array
        // containing the KEY to rate limit
        $meta = call_user_func_array($this->settings['callback'], array($request));

        // If the callback returns true, we take that as good to go. This will not add
        // a Rate Limit header to the response
        if($meta === TRUE) {
            return $next($request, $response);
        }

        if(!array_key_exists('key', $meta)) {
            throw new \InvalidArgumentException("Callback must return array with 'key' value");
        }

        // Now we have our key.
        // Step 1: Get our data from Redis
        return $next($request, $response);

    }

    protected function fetchBucket($key) {

    }
}
