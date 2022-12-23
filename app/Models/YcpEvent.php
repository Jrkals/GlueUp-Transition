<?php

namespace App\Models;

use App\Helpers\NBTagParser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YcpEvent extends Model {
    use HasFactory;

    public function contacts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        return $this->belongsToMany( YcpContact::class, 'ycp_events_contacts' )->withPivot( 'attended' );
    }

    public static function getEvent( array $row ): ?YcpEvent {
        $date  = $row['event_date'] ? Carbon::parse( $row['event_date'] )->toDateString() : null;
        $event = YcpEvent::query()->where( [
            'name' => $row['event'],
            'date' => $date
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

    private static function getEventType( string $eventName ): string {
        $eventName = strtolower( $eventName );
        if ( str_contains( $eventName, 'ess' ) || str_contains( $eventName, 'speaker series' )
             || str_contains( $eventName, 'executive speaker' ) ) {
            return 'ESS';
        }
        if ( str_contains( $eventName, 'nhh' ) || str_contains( $eventName, 'happy hour' )
             || str_contains( $eventName, 'networking social' ) ) {
            return 'NHH';
        }
        if ( str_contains( $eventName, 'sjs' ) || str_contains( $eventName, 'saint joseph' ) || str_contains( $eventName, 'st. joseph' )
             || str_contains( $eventName, 'retreat' ) ) {
            return 'SJS';
        }
        if ( str_contains( $eventName, 'panel' ) ) {
            return 'Panel';
        }
        if ( str_contains( $eventName, 'conference' ) ) {
            return 'Conference';
        }
        if ( str_contains( $eventName, 'launch' ) ) {
            return 'Launch';
        }

        return 'Other';
    }

    public static function fromNBTag( string $tag, string $date, YcpContact $contact = null ): YcpEvent {
        $event       = new YcpEvent();
        $event->name = str( $tag )->trim()->value();
        $event->date = $date ?: null;
        $event->type = self::getEventType( $tag );
        $event->save();

        return $event;
    }


}
