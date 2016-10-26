<?php

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use Zend\Diactoros\ServerRequest as Request;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;

class RateLimiterTest extends TestCase {
    protected $storage = [
        'scheme' => 'tcp',
        'host' => 'redis',
        'port' => 6379
    ];

    public function setUp() {

    }

    public function testIgnore() {

    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testUnspecifiedCallback() {
        $limiter = new LeakyBucketRateLimiter\RateLimiter([
            'throttle' => function() {}
        ]);
        $limiter();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testUnspecifiedThrottleCallback() {
        $limiter = new LeakyBucketRateLimiter\RateLimiter([
            'callback' => function() {}
        ]);
        $limiter();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidCallback() {
        $limiter = new LeakyBucketRateLimiter\RateLimiter([
            'callback' => function($request) {

            },
            'throttle' => 'invalid_callback'
        ], $this->storage);
        $request = (new Request)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");
        $response = new Response;
        $limiter($request, $response, function() {});
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidThrottle() {
        $limiter = new LeakyBucketRateLimiter\RateLimiter([
            'callback' => function($request) {
                return [
                    'key' => uniqid()
                ];
            },
            'throttle' => 'invalid_callback'
        ], $this->storage);
        $request = (new Request)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");
        $response = new Response;
        $limiter($request, $response, function() {});
    }

    public function testMetaAsTrue() {

    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMetaNotArray() {
        $limiter = new LeakyBucketRateLimiter\RateLimiter([
            'callback' => function($request) {
                return 'testing';
            },
            'throttle' => '',
        ], $this->storage);
        $request = (new Request)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");
        $response = new Response;
        $limiter($request, $response, function() {});
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMetaDoesNotContainTokenKey() {
        $limiter = new LeakyBucketRateLimiter\RateLimiter([
            'callback' => function($request) {
                return [
                    'testing' => true
                ];
            },
            'throttle' => '',
        ], $this->storage);
        $request = (new Request)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");
        $response = new Response;
        $limiter($request, $response, function() {});
    }

    public function testBucketIsFull() {

    }


    public function testDefaultHeader() {
        $result = null;
        $limiter = new LeakyBucketRateLimiter\RateLimiter([
            'callback' => function($request) {
                return [
                    'token' => uniqid()
                ];
            },
            'throttle' => ''
        ], $this->storage);
        $request = (new Request)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");
        $response = new Response;
        $limiter($request, $response, function($req, $res) {
            $this->assertContains("X-Rate-Limit", array_keys($res->getHeaders()));
        });
    }

    public function testCustomHeader() {
        $result = null;
        $limiter = new LeakyBucketRateLimiter\RateLimiter([
            'callback' => function($request) {
                return [
                    'token' => uniqid()
                ];
            },
            'throttle' => '',
            'header' => 'X-Api-Rate-Limit'
        ], $this->storage);
        $request = (new Request)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");
        $response = new Response;
        $limiter($request, $response, function($req, $res) {
            $this->assertContains("X-Api-Rate-Limit", array_keys($res->getHeaders()));
        });
    }

    public function testDisabledHeader() {
        $result = null;
        $limiter = new LeakyBucketRateLimiter\RateLimiter([
            'callback' => function($request) {
                return [
                    'token' => uniqid()
                ];
            },
            'throttle' => '',
            'header' => false
        ], $this->storage);
        $request = (new Request)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");
        $response = new Response;
        $limiter($request, $response, function($req, $res) {
            $this->assertEmpty(array_keys($res->getHeaders()));
        });
    }

    public function testPrefix() {

    }

    public function testSuffix() {
        
    }
}
