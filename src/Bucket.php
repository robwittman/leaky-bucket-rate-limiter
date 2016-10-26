<?php

namespace LeakyBucketRateLimiter;

class Bucket {
    /**
     * Total drips bucket holds
     * @var integer
     */
    protected $capacity;

    /**
     * Rate at which bucket leaks (drips / sec)
     * @var float
     */
    protected $leak_rate;

    /**
     * Current number of drips in bucket
     * @var integer
     */
    protected $drips = 0;

    /**
     * Current timestamp of bucket
     * @var float
     */
    protected $time;

    public function __construct($data = null) {
        $this->setTime(microtime(true));
        if(!is_null($data)) {
            if(array_key_exists('drips', $data)) $this->setDrips($data['drips']);
            if(array_key_exists('time', $data)) $this->setTime($data['time']);
        }
    }

    /**
     * Add an amount of drips to bucket
     * @param  integer $x
     * @return
     */
    public function fill($x = 1) {
        $drips = $this->getDrips();
        $this->setDrips($drips += $x);
    }

    /**
     * Check if our bucket is full
     * @return boolean [description]
     */
    public function isFull() {
        return $this->drips >= $this->getCapacity();
    }

    /**
     * Set the max capacity of this bucket
     * @param integer $capacity
     */
    public function setCapacity($capacity) {
        $this->capacity = $capacity;
    }

    /**
     * Read the max capacity of the bucket
     * @return integer
     */
    public function getCapacity() {
        return $this->capacity;
    }

    /**
     * Set the rate (drips / sec), that bucket leaks
     * @param float $rate
     */
    public function setLeakRate($rate) {
        $this->leak_rate = $rate;
    }

    /**
     * Get previously set leak rate
     * @return float
     */
    public function getLeakRate() {
        return $this->leak_rate;
    }

    /**
     * Get number of drips left
     * @return integer
     */
    public function getCapacityRemaining() {
        return $this->capacity - $this->drips;
    }

    public function getCapacityString() {
        return  round($this->getDrips()).' / '.$this->getCapacity();
    }

    /**
     * Get drips in bucket
     * @return integer
     */
    public function getDrips() {
        return $this->drips;
    }

    /**
     * Set the number of drips in bucket
     * @param $drips
     */
    public function setDrips($drips) {
        $this->drips = $drips;
    }

    public function getData() {
        return [
            'drips' => $this->getDrips(),
            'time' => $this->getTime()
        ];
    }

    /**
     * Get time remaining until next available drip
     * @return float
     */
    public function timeRemaining() {

    }

    public function setTime($time) {
        $this->time = $time;
        return $this;
    }

    public function getTime() {
        return $this->time;
    }

    public function leak($leak_rate = null) {
        // Find out what our leak rate is
        $rate = is_null($leak_rate) ? $this->getLeakRate() : $leak_rate;

        $elapsed = microtime(true) - $this->time;
        $leakage = $elapsed * $rate;
        $drips = $this->getDrips() ?: 0;
        $this->setDrips($drips -= $leakage);
        $this->setTime(microtime(true));

        if($this->getDrips() < 0) $this->setDrips(0);
        return $this;
    }
}
