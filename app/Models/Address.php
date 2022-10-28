<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model {
    use HasFactory;

    private string $street1;
    private string $street2;
    private string $postalCode;
    private string $country;
    private string $city;
    private string $state;

    public function addressable() {
        return $this->morphTo();
    }

    private static function createFromAddress( $street1, $street2, $city, $state, $zip, $country ): Address {
        $address             = new Address();
        $address->street1    = $street1;
        $address->street2    = $street2;
        $address->city       = $city;
        $address->state      = $state;
        $address->postalCode = $zip;
        $address->country    = $country;
        $address->save();

        return $address;
    }

    public static function fromCSV( array $address ): Address {
        return self::createFromAddress( $address['street_address'], $address['street_address_2'], $address['city'],
            $address['province'], $address['postalzip_code'], $address['country'] );
    }

    public function street1(): string {
        return $this->street1;
    }

    public function street2(): string {
        return $this->street2;
    }

    public function city(): string {
        return $this->city;
    }

    public function state(): string {
        return $this->state;
    }

    public function postalCode(): string {
        return $this->postalCode;
    }

    public function country(): string {
        return $this->country;
    }
}
