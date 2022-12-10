<?php

namespace App\Helpers;

use JetBrains\PhpStorm\Pure;

class Name {

    private string $firstName;
    private string $lastName;
    private string $fullName;

    #[Pure] public static function fromFullName( string $fullName ): Name {
        $parts = explode( " ", $fullName );
        if ( sizeof( $parts ) === 1 ) {
            return new Name( $fullName, '', $fullName );
        }
        if ( sizeof( $parts ) === 2 ) {
            return new Name( $parts[0], $parts[1], $fullName );
        }

        return new Name( $parts[0], implode( " ", [ $parts[1], $parts[2] ] ), $fullName );
    }

    private function __construct( $fn, $ln, $full ) {
        $this->firstName = $fn ? str( $fn )->title() : '';
        $this->lastName  = $ln ? str( $ln )->title() : '';
        $this->fullName  = $full ? str( $full )->title() : '';
    }

    public function firstName(): string {
        return $this->firstName;
    }

    public function lastName(): string {
        return $this->lastName;
    }

    public function fullName(): string {
        return $this->fullName;
    }
}
