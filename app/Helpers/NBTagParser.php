<?php

namespace App\Helpers;

use App\Models\YcpContact;
use App\Models\YcpEvent;
use Carbon\Carbon;

class NBTagParser {
    private string $tagString;
    private YcpContact $contact;
    private array $tags;

    public function __construct( string $tags, YcpContact $contact ) {
        $this->tagString = $tags;
        $this->tags      = explode( ',', $tags );
        $this->contact   = $contact;
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
        echo $tag . " is not event tag" . "\n";

        return false;
    }

    private function getTagDate( string $tag ): string {
        $separator = $this->findSeparator( $tag );
        $parts     = explode( $separator, $tag );
        foreach ( $parts as $part ) {
            if ( Carbon::parse( $part )->isValid() ) {
                return $part;
            }
            if ( str_contains( $part, '16' ) || str_contains( $part, '2016' ) ) {
                return Carbon::create( 2016 )->toDateString();
            }
            if ( str_contains( $part, '17' ) || str_contains( $part, '2017' ) ) {
                return Carbon::create( 2017 )->toDateString();
            }
            if ( str_contains( $part, '18' ) || str_contains( $part, '2018' ) ) {
                return Carbon::create( 2018 )->toDateString();
            }
            if ( str_contains( $part, '19' ) || str_contains( $part, '2019' ) ) {
                return Carbon::create( 2019 )->toDateString();
            }
            if ( str_contains( $part, '20' ) || str_contains( $part, '2020' ) ) {
                return Carbon::create( 2020 )->toDateString();
            }
        }

    }

    private function findSeparator( string $tag ): string {
        if ( sizeof( str( $tag )->split( ' ' ) ) > 1 ) {
            return ' ';
        }
        if ( sizeof( str( $tag )->split( '-' ) ) > 1 ) {
            return '-';
        }
        if ( sizeof( str( $tag )->split( '_' ) ) > 1 ) {
            return '_';
        }

        return '';
    }

}