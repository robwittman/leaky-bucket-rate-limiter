<?php

/**
 *
 */

namespace LeakyBucketRateLimiter;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use LeakyBucketRateLimiter\Bucket;

class RateLimiter {

    /**
     * Sensible defaults
     * @var array
     */
    private static $defaults = array(
        'capacity' => 20,
        'leak'     => 1,
        'callback' => null,
        'header'   => 'X-Rate-Limit',
        'prefix'   => "rate_limiter:",
        'suffix'   => ":bucket",
        'ignore'   => [],
        'throttle' => null
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
     * Placeholder for ou actual bucket
     * @var LeakyBucketRateLimiter\Bucket;
     */
    private $bucket;

    /**
     * The full key to limit on
     * @var string
     */
    private $key;

    /**
     * Create a new Rate Limiter
     *
     * @param array $settings
     * @param mixed $storage   Mechanism for storing and retrieving rate data
     */
    public function __construct(array $settings = array(), $storage = null) {
        $settings       = array_intersect_key($settings, self::$defaults);
        $this->settings = array_merge(self::$defaults, $settings);

        if(is_null($this->settings['throttle'])) { throw new \InvalidArgumentException("Callback required for Rate Limiter"); }
        if(is_null($this->settings['callback'])) { throw new \InvalidArgumentException("Throttle response required for Rate Limiter"); }

        if(is_null($storage) ) {
            $this->storage = new \Predis\Client();
        } else if(is_array($storage) ) {
            $this->storage = new \Predis\Client($storage);
        } else {
            $this->storage = $storage;
        }
    }

    /**
     * Execute our Rate Limiter
     * @param  \Psr\Http\Message\RequestInterface  $request
     * @param  \Psr\Http\Message\ResponseInterface $response
     * @param  callable          $next
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next) {

        // Check if our route is present in the unlimited settings
        $route = rtrim($request->getUri()->getPath(), '/');

        foreach($this->settings['ignore'] as $ignored_route) {
            if(!!preg_match("@^{$ignored_route}(/.)?$@", $route)) {
                return $next($request, $response);
            }
        }
        // Run our user-supplied function to get the key we can limit on
        $meta = call_user_func($this->settings['callback'], array($request));

        // $meta of TRUE triggers a rate limit override
        if($meta === TRUE) { return $next($request, $response); }

        if(!is_array($meta) || !array_key_exists('token', $meta)) {
            throw new \InvalidArgumentException('Callback must return array \'$meta\' with \'token\' value');
        }
        $this->setKey($meta['token']);

        // Create our bucket from our key
        $bucket = new Bucket($this->get($this->getKey()));

        $bucket->setCapacity($this->settings['capacity']);
        $bucket->setLeakRate($this->settings['leak']);
        $bucket->leak();

        if($bucket->isFull()) {
            return call_user_func($this->settings['throttle'], $response, $bucket, $this->settings);
        }

        $bucket->fill();
        $this->save($bucket);
        if($this->settings['header'] !== FALSE) {
            $response = $response->withHeader($this->settings['header'], $bucket->getCapacityString());
        }
        return $next($request, $response);
    }

    /**
     * Fetch our bucket data from storage
     * @param  string $key
     * @return void
     */
    protected function fetchBucket($key) {
        $data = $this->storage->get($key);
        return json_decode($data, TRUE);
    }

    /**
     * Save our bucket to storage
     * @param  object $bucket
     * @return boolean
     */
    protected function save($bucket) {
        return $this->storage->set($this->key, json_encode($bucket->getData()));
    }

    /**
     * Fetch our bucket from storage
     * @param  string $key
     * @return
     */
    protected function get($key) {
        return json_decode($this->storage->get($key), TRUE);
    }

    /**
     * Add prefix / suffix to key, and store in property
     * @param string $key Key to throttle on
     */
    protected function setKey($key) {
        $this->key = $this->settings['prefix'].$key.$this->settings['suffix'];
        return true;
    }

    /**
     * Return our compiled key
     * @return string
     */
    public function getKey() {
        return $this->key;
    }
}
