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
            str_contains( $tag, "kickoff" )
        ) {
            return true;
        }

        return false;
    }

    private function getTagDate( string $tag ): string {
        $separator = $this->findSeparator( $tag );
        $parts     = $separator ? explode( $separator, $tag ) : [ $tag ];
        $builder   = '';
        foreach ( $parts as $part ) {
            if ( $this->isDate( $part ) ) {
                $builder = '';

                return $this->getDate( $part );
            }
            if ( is_numeric( $part ) ) {
                $builder .= $part . '-';
                if ( $this->isDate( str( $builder )->trim( '-' )->value() ) ) {
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
        // e.g. 2018, 030119
        if ( ( $len === 6 || $len === 4 ) && $isNumeric ) {
            return true;
        }
        if ( $len === 2 ) {
            return false;
        }
        if ( str_contains( $part, '/' ) ) {
            return true;
        }
        if ( substr_count( $part, '-' ) === 2 && ! str_ends_with( $part, '-' ) ) {
            return true;
        }

        return false;
    }

    private function getDate( string $part ): string {
        $part      = str( $part )->trim()->value();
        $len       = strlen( $part );
        $isNumeric = is_numeric( $part );
        if ( $isNumeric && $len === 4 ) {
            return Carbon::create( $part )->toDateString();
        }

        if ( $len === 6 && $isNumeric ) {
            $a = Carbon::create( '20' . substr( $part, 0, 2 ), substr( $part, 2, 2 ),
                substr( $part, 4, 2 ) )->toDateString();

            return Carbon::create( '20' . substr( $part, 0, 2 ), substr( $part, 2, 2 ),
                substr( $part, 4, 2 ) );
        }

        $a = Carbon::parse( $part )->toDateString();

        return Carbon::parse( $part )->toDateString();
    }

    private function findSeparator( string $tag ): string {
        if ( empty( $tag ) ) {
            return $tag;
        }
        $tag = str( $tag )->trim( ' ' )->value();
        if ( sizeof( explode( ' ', $tag ) ) > 1 ) {
            return ' ';
        }
        if ( sizeof( explode( '-', $tag ) ) > 1 ) {
            return '-';
        }
        if ( sizeof( explode( '_', $tag ) ) > 1 ) {
            return '_';
        }

        return '';
    }

}
