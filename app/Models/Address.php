<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model {
    use HasFactory;

    protected $fillable = [
        'street1',
        'postal_code',
        'country',
        'city',
        'state'
    ];

    public static function makeHomeAddressDto( array $csvRow ) {
        return [
            'street1'     => $csvRow['home_street_address'],
            'street2'     => $csvRow['home_street_address_2'] ?? '',
            'city'        => $csvRow['home_city'],
            'state'       => $csvRow['home_province'] ?? '',
            'postal_code' => $csvRow['home_postal'] ?? '',
            'country'     => $csvRow['home_country'] ?? '',
        ];
    }

    public static function makeWorkAddressDto( array $csvRow ) {
        return [
            'street1'     => $csvRow['work_street_address'],
            'street2'     => $csvRow['work_street_address_2'] ?? '',
            'city'        => $csvRow['work_city'],
            'state'       => $csvRow['work_province'] ?? '',
            'postal_code' => $csvRow['work_postal'] ?? '',
            'country'     => $csvRow['work_country'] ?? '',
        ];
    }

    public static function fromFull( string $address ): Address {
        if ( ! $address ) {
            return new Address();
        }
        $parts = explode( ',', $address );

        if ( sizeof( $parts ) === 5 ) {
            return new Address( [
                'street1'     => $parts[0],
                'city'        => $parts[1],
                'state'       => $parts[2],
                'postal_code' => $parts[4],
                'country'     => $parts[3]
            ] );
        } else if ( sizeof( $parts ) === 6 ) {
            return new Address( [
                'street1'     => $parts[0] . "\n" . $parts[1],
                'city'        => $parts[2],
                'state'       => $parts[3],
                'postal_code' => $parts[5],
                'country'     => $parts[4]
            ] );
        } else {
            return new Address();
        }

    }

    public function addressable() {
        return $this->morphTo();
    }

    private static function createFromAddress( $street1, $street2, $city, $state, $zip, $country, $addressable_type, $id, $type ): Address {
        $address                   = new Address();
        $address->street1          = ( $street2 ) ? $street1 . "\n" . $street2 : $street1;
        $address->city             = $city;
        $address->state            = $state;
        $address->postal_code      = $zip;
        $address->country          = $country;
        $address->addressable_type = $addressable_type === 'contact' ? YcpContact::class : YcpCompany::class;
        $address->addressable_id   = $id;
        $address->address_type     = $type;
        $address->save();

        return $address;
    }

    public static function fromCSV( array $address, string $addressable_type, int $id ): Address {
        return self::createFromAddress( $address['street_address'], $address['street_address_2'], $address['city'],
            $address['province'], $address['postalzip_code'], $address['country'], $addressable_type, $id, 'business' );
    }

    public static function fromCSVHome( array $address, string $addressable_type, int $id ): Address {
        return self::createFromAddress( $address['home_street_address'], $address['home_address_2'], $address['home_city'],
            $address['home_province'], $address['home_postal'], $address['home_country'], $addressable_type, $id, 'home' );
    }

    public static function fromCSVWork( array $address, string $addressable_type, int $id ): Address {
        return self::createFromAddress( $address['work_street_address'], $address['work_address_2'], $address['work_city'],
            $address['work_province'], $address['work_postal'], $address['work_country'], $addressable_type, $id, 'business' );
    }

    public function isSame( array $address ): bool {
        return $this->city() === $address['city'] && $this->postalCode() === $address['postal_code']
               && $this->state() === $address['state'] && $this->sameStreet( $this->street1(), $address['street1'] );
    }

    private function sameStreet( string $street1, string $street ): bool {
        return $street1 === $street;
    }
}
