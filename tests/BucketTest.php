<?php

use PHPUnit\Framework\TestCase;

class BucketTest extends TestCase {

    public function setUp() {
        $this->bucket = new LeakyBucketRateLimiter\Bucket();
    }

    public function testGetCapacity() {
        $this->bucket->setCapacity(20);
        $cap = $this->bucket->getCapacity();
        $this->assertEquals($cap, 20);
    }

    public function testGetDrips() {
        $this->assertEquals($this->bucket->getDrips(), 0);
    }

    public function testSetDrips() {
        $this->bucket->setDrips(45);
        $this->assertEquals($this->bucket->getDrips(), 45);
    }

    public function testSetTime() {
        $time = microtime(true);
        $this->bucket->setTime($time);
        $this->assertEquals($this->bucket->getTime(), $time);
    }

    public function testFill() {
        $drips = $this->bucket->getDrips();
        $this->bucket->fill();
        $drips++;
        $this->assertEquals($drips, $this->bucket->getDrips());
        $this->bucket->fill(15);
        $this->assertEquals($drips += 15, $this->bucket->getDrips());
    }

    public function testLeakRate() {
        $this->bucket->setLeakRate(1.33);
        $this->assertEquals($this->bucket->getLeakRate(), 1.33);
    }

    public function testLeak() {
        // TODO: Write test method for leak
    }
}
