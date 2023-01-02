<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Propaganistas\LaravelPhone\PhoneNumber;


class Phone extends Model {
    use HasFactory;

    protected $fillable = [
        'type',
        'number',
        'ycp_contact_id',
    ];

    public function contact() {
        return $this->belongsTo( YcpContact::class );
    }

    public static function create( string $number, int $contact_id, string $type ): ?Phone {
        $phone                 = new Phone();
        $phone->number         = self::format( $number );
        $phone->type           = $type;
        $phone->ycp_contact_id = $contact_id;
        $phone->save();

        return $phone;
    }

    /**
     * @throws \Propaganistas\LaravelPhone\Exceptions\CountryCodeException
     */
    public function sameAs( string $phone ): bool {
        try {
            $thisNumber = PhoneNumber::make( self::stripSpecialCharacters( $this->number ), [ 'US' ] )->formatForCountry( 'US' );
        } catch ( \Exception $exception ) {
            return false;
        }
        try {
            $compareTo = PhoneNumber::make( self::stripSpecialCharacters( $phone ), [ 'US' ] )->formatForCountry( 'US' );
        } catch ( \Exception $exception ) {
            return false;
        }

        return $thisNumber === $compareTo;

    }

    public static function stripSpecialCharacters( string $phoneNumber ) {
        return str_replace( [ '(', ')', '-', ' ', '+', "'", '.', ' ' ], '', $phoneNumber );
    }


    public static function format( string $number ): string {
        try {
            $number = PhoneNumber::make( $number, [ 'US' ] )->formatForCountry( 'US' );
        } catch ( \Exception $exception ) {
            return '';
        }

        return $number;
    }
}
