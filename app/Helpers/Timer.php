<?php

namespace App\Helpers;

use Carbon\Carbon;
use Carbon\Exceptions\Exception;

class Timer {
    private float $start;
    private float $timeElapsed;
    private float $previousTotal;
    private float $previousRate;

    public function __construct() {
        $this->timeElapsed = 0;
    }

    public function start() {
        $this->start = microtime( true );
    }

    public function elapsed( string $prepend = '' ) {
        $this->timeElapsed = ( microtime( true ) - $this->start );

        return $prepend . ' Elapsed: ' . $this->timeElapsed . ' s';
    }

    public function progress( $progress, $total ) {
        $this->timeElapsed = ( microtime( true ) - $this->start );
        $rate              = $progress / $this->timeElapsed;
        $amountRemaining   = $total - $progress;
        $timeRemaining     = $amountRemaining / $rate;

        return ' Elapsed: ' . number_format( $this->timeElapsed, 1 ) . 's' . "\t" .
               number_format( $rate, 1 ) . '/s' . "\t" . number_format( $timeRemaining, 1 )
               . 's remaining' . "\t" . number_format( $amountRemaining ) . ' items remaining ';
    }

    public function end() {
        $this->timeElapsed = microtime() - $this->start;
    }


}
