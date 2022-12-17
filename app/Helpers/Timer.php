<?php

namespace App\Helpers;

use Carbon\Carbon;
use Carbon\Exceptions\Exception;

class Timer {
    private float $start;
    private float $timeElapsed;

    public function __construct() {
        $this->timeElapsed = 0;
    }

    public function start() {
        $this->start = microtime( true );
    }

    public function elapsed( string $prepend = '' ) {
        $this->timeElapsed = ( microtime( true ) - $this->start );

        return $prepend . ' Elapsed: ' . number_format( $this->timeElapsed, 1 ) . ' s';
    }

    public function progress( $progress, $total ) {
        $this->timeElapsed = ( microtime( true ) - $this->start );
        $rate              = $progress / $this->timeElapsed;
        $amountRemaining   = $total - $progress;
        $timeRemaining     = $amountRemaining / $rate;
        if ( $amountRemaining <= 0 ) {
            return 'Done in ' . $this->timeElapsed;
        }

        return ' Elapsed: ' . number_format( $this->timeElapsed, 1 ) . 's' . "\t" .
               number_format( $rate, 1 ) . '/s' . "\t" . number_format( $timeRemaining, 1 )
               . 's remaining' . "\t" . number_format( $amountRemaining ) . ' items remaining ';
    }

}
