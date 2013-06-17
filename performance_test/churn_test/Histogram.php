<?php

class ExponentialGenerator {
    public static function generate($start, $end, $ratio) {
        $res = array();
        if ($ratio <= 1.0) {
            return $res;
        }
        while ($start <= $end) {
            $next = (int) ($start * $ratio);
            if ($next == $start) {
                $next++;
            }
            $res[] = array($start, $next);
            $start = $next;
        }
        return $res;
    }
}

class Histogram {
    private $binMap;
    private $min;
    private $max;
    private $size;
    private $total;

    private function validateBins($bins) {
        if (count($bins) == 0) {
            return TRUE;
        }
        $f = $bins[0][0];
        foreach ($bins as $b) {
            if ($b[0] < $f || $b[0] >= $b[1]) {
                return FALSE;
            }
            $f = $b[1];
        }
        return TRUE;
    }

    public function __construct($bins = array()) {
        $this->binMap = array();
        if ($this->validateBins($bins)) {
            foreach ($bins as $b) {
                $this->binMap[] = array($b[0], $b[1], 0);
            }
        } else {
            print("WARNING: Bins invalid, histogram will not be built.");
        }
        $this->min = 0;
        $this->max = 0;
        $this->size = 0;
        $this->total = 0;
    }

    public function add($key, $count = 1) {
        $l = count($this->binMap);
        for ($i = 0; $i < $l; $i++) {
            if ($this->binMap[$i][0] <= $key && $key < $this->binMap[$i][1]) {
                $this->binMap[$i][2] += $count;
                if ($this->size == 0) {
                    $this->min = $key;
                    $this->max = $key;
                } else {
                    if ($key < $this->min) {
                        $this->min = $key;
                    } else if ($key > $this->max) {
                        $this->max = $key;
                    }
                }
                $this->size += $count;
                $this->total += $count * $key;
                return TRUE;
            }
        }
        return FALSE;
    }

    public function addHisto($h) {
        $l = count($this->binMap);
        if ($l != count($h->binMap)) {
            return FALSE;
        }
        for ($i = 0; $i < $l; $i++) {
            if ($this->binMap[$i][0] != $h->binMap[$i][0]
                || $this->binMap[$i][1] != $h->binMap[$i][1]) {
                return FALSE;
            }
        }
        if ($h->size > 0) {
            for ($i = 0; $i < $l; $i++) {
                $this->binMap[$i][2] += $h->binMap[$i][2];
            }
            $this->size += $h->size;
            $this->total += $h->total;
            if ($this->size == 0 || $h->min < $this->min) {
                $this->min = $h->min;
            }
            if ($this->size == 0 || $h->max > $this->max) {
                $this->max = $h->max;
            }
        }
        return TRUE;
    }

    public function isSubset($h) {
        $l = count($this->binMap);
        if ($l != count($h->binMap)) {
            return FALSE;
        }
        for ($i = 0; $i < $l; $i++) {
            if ($this->binMap[$i][0] != $h->binMap[$i][0]
                || $this->binMap[$i][1] != $h->binMap[$i][1]) {
                return FALSE;
            }
        }
        if ($h->size > 0) {
            for ($i = 0; $i < $l; $i++) {
                if ($h->binMap[$i][2] > $this->binMap[$i][2]) {
                    return FALSE;
                }
            }
        }
        return TRUE;
    }

    public function getMin() {
        return $this->min;
    }

    public function getMax() {
        return $this->max;
    }

    public function getAvg() {
        return $this->total / $this->size;
    }

    public function getSize() {
        return $this->size;
    }

    public function getStr($name, $printZero = FALSE) {
        $res = "{$name} histogram:\n";
        $l = count($this->binMap);
        for ($i = 0; $i < $l; $i++) {
            if ($printZero || $this->binMap[$i][2] > 0) {
                $res .= "   [{$this->binMap[$i][0]}, {$this->binMap[$i][1]}] = {$this->binMap[$i][2]}\n";
            }
        }
        if ($this->size > 0) {
            $res .= "\n";
            $res .= "Max = {$this->max}\n";
            $res .= "Min = {$this->min}\n";
            $res .= "Avg = {$this->getAvg()}\n";
        }
        $res .= "\n";
        return $res;
    }

    public function printHisto($name, $printZero = FALSE) {
        print($this->getStr($name, $printZero));
    }

    public function getBins() {
        // TO BE WRITTEN
    }
}

?>
