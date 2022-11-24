<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YcpEvent extends Model {
    use HasFactory;

    public function contacts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        return $this->belongsToMany( YcpContact::class, 'ycp_events_contacts' )->withPivot( 'attended' );
    }

    //TODO make sure these criteria are strick enough
    public static function getEvent( array $row ): ?YcpEvent {
        $event = YcpEvent::query()->where( [
            'name' => $row['event'],
            'date' => Carbon::parse( $row['event_date'] )->toDateString()
        ] )->first();

        if ( ! $event ) {
            return null;
        }

        return $event;
    }

    public static function fromCSV( array $row ) {
        $event       = new YcpEvent();
        $event->name = $row['event'];
        $event->date = Carbon::parse( $row['event_date'] )->toDateString();
        $event->type = self::getEventType( $row['event'] );
        $event->save();
        $event->contacts()->save( YcpContact::getOrCreateContact( $row['attendee_name'], $row['email'] ),
            [ 'attended' => $row['attended'] === 'True' ] );
    }

    private static function getEventType( string $eventName ) {
        if ( str_contains( $eventName, 'ESS' ) || str_contains( $eventName, 'Speaker Series' )
             || str_contains( $eventName, 'Executive Speaker' ) ) {
            return 'ESS';
        }
        if ( str_contains( $eventName, 'Saint Joseph' ) || str_contains( $eventName, 'St. Joseph' )
             || str_contains( $eventName, 'Retreat' ) ) {
            return 'SJS';
        }
        if ( str_contains( $eventName, 'NHH' ) || str_contains( $eventName, 'Happy Hour' )
             || str_contains( $eventName, 'Networking Social' ) ) {
            return 'NHH';
        }
        if ( str_contains( $eventName, 'Panel' ) ) {
            return 'Panel';
        }
        if ( str_contains( $eventName, 'Conference' ) ) {
            return 'Conference';
        }
        if ( str_contains( $eventName, 'Launch' ) ) {
            return 'Launch';
        }

        return 'Other';
    }


}
