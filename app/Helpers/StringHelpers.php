<?php

namespace App\Helpers;

class StringHelpers {

    public static function validateUrl( string $url ): string {
        if ( empty( $url ) ) {
            return $url;
        }
        if ( ! str_contains( $url, 'linkedin.com' ) || strlen( $url ) > 255 ) {
            return '';
        }
        if ( ( str_starts_with( $url, 'https://' ) || str_starts_with( $url, 'http://' ) ) && str_contains( $url, '.com' ) ) {
            return $url;
        }
        if ( str_starts_with( $url, 'linkedin.com' ) || str_starts_with( $url, 'www.linkedin.com' ) ) {
            return 'https://' . $url;
        }

        return '';
    }

    /**
     * @param string $answer
     * Turns string to lowercase and replaces spaces with dash
     *
     * @return string
     */
    public static function glueUpSlugify( string $answer ): string {
        return strtolower( str_replace( ' ', '-', $answer ) );
    }
}
