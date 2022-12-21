<?php

namespace App\Helpers;

use App\Models\YcpContact;
use App\Models\YcpEvent;
use Carbon\Carbon;

class NBTagParser {
    private YcpContact $contact;
    private array $tags;

    public function __construct( string $tags, YcpContact $contact ) {
        $this->tags    = explode( ',', $tags );
        $this->contact = $contact;
    }

    public function makeEvents(): array {
        $events = [];
        foreach ( $this->tags as $tag ) {
            if ( $this->isEventTag( $tag ) ) {
                $date     = $this->getTagDate( $tag );
                $event    = YcpEvent::fromNBTag( $tag, $date, $this->contact );
                $events[] = $event;
            }
        }

        return $events;
    }

    private function isEventTag( string $tag ): bool {
        $tag = strtolower( $tag );
        if (
            str_contains( $tag, "ess" ) ||
            str_contains( $tag, "nhh" ) ||
            str_contains( $tag, "sjs" ) ||
            str_contains( $tag, "sjr" ) ||
            str_contains( $tag, "panel" ) ||
            str_contains( $tag, "attendee" ) ||
            str_contains( $tag, "kickoff" ) ||
            str_contains( $tag, 'event' )
        ) {
            return true;
        }

        return false;
    }

    private function getTagDate( string $tag ): string {
        $parts   = $this->explodeString( $tag );
        $builder = '';
        $size    = sizeof( $parts );
        for ( $i = 0; $i < $size; $i ++ ) {
            $part = $parts[ $i ];
            // $part          = $this->mapMonth( $part );
            $next          = $parts[ $i + 1 ] ?? '';
            $nextIsNumeric = is_numeric( $next );
            if ( $this->isDate( $part ) && $builder === '' && ! $nextIsNumeric ) {
                return $this->getDate( $part );
            }
            if ( is_numeric( $part ) ) {
                $builder .= $part . '-';
                if ( $this->isDate( str( $builder )->trim( '-' )->value() )
                     && ! $nextIsNumeric ) {
                    return $this->getDate( str( $builder )->trim( '-' )->value() );
                }
            }
        }

        return '';
    }

    private function isDate( $part ): bool {
        $part      = str( $part )->trim()->value();
        $len       = strlen( $part );
        $isNumeric = is_numeric( $part );
        $dashCount = substr_count( $part, '-' );
        // e.g. 2018, 030119
        if ( ( $len === 6 || $len === 4 ) && $isNumeric ) {
            return true;
        }
        if ( $len < 3 ) {
            return false;
        }
        if ( str_contains( $part, '/' ) ) {
            return true;
        }
        if ( $dashCount === 2 ) {
            return true;
        }

        return false;
    }

    private function getDate( string $part ): string {
        $part      = str( $part )->trim()->value();
        $len       = strlen( $part );
        $isNumeric = is_numeric( $part );
        $dashCount = substr_count( $part, '-' );
        if ( $isNumeric && $len === 4 ) {
            return Carbon::create( $part )->toDateString();
        }

        if ( $len === 6 && $isNumeric ) {
            return Carbon::create( '20' . substr( $part, 0, 2 ), substr( $part, 2, 2 ),
                substr( $part, 4, 2 ) );
        }

        if ( $dashCount === 2 ) {
            return $this->getYearFromTwoDashes( $part );
        }

        return Carbon::parse( $part )->toDateString();
    }

    private function explodeString( string $tag ): array {
        $tag = str( $tag )->trim()->value();
        if ( empty( $tag ) ) {
            return [ $tag ];
        }
        $tag = str_replace( ' ', '-', $tag );
        $tag = str_replace( '_', '-', $tag );
        $tag = str( $tag )->trim( ' ' )->value();

        return explode( '-', $tag );
    }

    // 1-7-20 -> 2020-01-07
    // 01-07-20 -> 2020-01-07
    // 01-07-2020 -> 2020-01-07
    // 2020-01-07 -> 2020-01-07
    private function getYearFromTwoDashes( string $date ) {
        $parts = explode( '-', $date );
        if ( strlen( $parts[0] ) === 4 ) {
            $year  = intval( $parts[0] );
            $month = intval( $parts[1] );
            $day   = intval( $parts[2] );

            return Carbon::create( $year, $month, $day );
        }
        if ( strlen( $parts[2] ) === 4 ) {
            $year  = intval( $parts[2] );
            $month = intval( $parts[0] );
            $day   = intval( $parts[1] );

            return Carbon::create( $year, $month, $day );
        }
        $year  = intval( '20' . $parts[2] );
        $month = intval( $parts[0] );
        $day   = intval( $parts[1] );

        return Carbon::create( $year, $month, $day );
    }

//    private function mapMonth( string $part ): string {
//        return match ( $part ) {
//            'Jan' => '1',
//            'Feb' => '2',
//            'Mar' => '3',
//            'Apr' => '4',
//            'Myr' => '5',
//            'Jun' => '6',
//            'Jul' => '7',
//            'Aug' => '8',
//            'Sep' => '9',
//            'Oct' => '10',
//            'Nov' => '11',
//            'Dec' => '12',
//            default => $part
//        };
//    }

}
