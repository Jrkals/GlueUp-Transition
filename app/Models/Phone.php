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
        $phone = new Phone();
        try {
            $formattedNumber = PhoneNumber::make( $number )->formatForCountry( 'US' );
        } catch ( \Exception $e ) {
            return null;
        }

        $phone->number         = $formattedNumber;
        $phone->type           = $type;
        $phone->ycp_contact_id = $contact_id;
        $phone->save();

        return $phone;
    }

    /**
     * @throws \Propaganistas\LaravelPhone\Exceptions\CountryCodeException
     */
    public function sameAs( string $phone ): bool {
        return self::stripSpecialCharacters( $this->number ) ===
               PhoneNumber::make( self::stripSpecialCharacters( $phone ) )->formatForCountry( 'US' );
    }

    public static function stripSpecialCharacters( string $phoneNumber ) {
        return str_replace( [ '(', ')', '-', ' ', '+', "'", '.' ], '', $phoneNumber );
    }
}