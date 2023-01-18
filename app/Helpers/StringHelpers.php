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
    public static function glueUpSlugify( ?string $answer ): string {
        if ( ! $answer ) {
            return '';
        }
        $answer = str_replace( [ '.', ',', ';', '&', ')', '(' ], '', $answer );

        return strtolower( str_replace( [ ' ' ], '-', $answer ) );
    }

    public static function mapChapterInterestList( ?string $interest ): string {
        if ( ! $interest ) {
            return '';
        }
        $interest = strtolower( self::glueUpSlugify( $interest ) );

        return match ( $interest ) {
            'birmingham' => 'birmingham',
            'brooklyn' => 'brooklyn',
            'charlotte' => 'charlotte',
            'colorado-springs' => 'colorado-springs',
            'el-paso' => 'el-paso',
            'fort-wayne' => 'fort-wayne',
            'fresno' => 'fresno',
            'greenville' => 'greenville',
            'indianapolis' => 'indianapolis',
            'knoxville' => 'knoxville',
            'memphis' => 'memphis',
            'new-havenhartford' => 'new-havenhartford',
            'north-jersey' => 'north-jersey',
            'puerto-rico' => 'puerto-rico',
            'queens' => 'queens',
            'san-francisco' => 'san-francisco',
            'seattle' => 'seattle',
            'susquehanna-valley-central-pa' => 'susquehanna-valley-central-pa',
            'tampast-pete' => 'tampast-pete',
            'toledo' => 'toledo',
            'washington-dc' => 'washington-dc',
            default => 'other-7e0f11f1',
        };
    }

    public static function mapChapterLeaderRole( string $role ): string {
        if ( empty( $role ) ) {
            return $role;
        }
        if ( ! str_contains( $role, ',' ) ) {
            return self::mapIndividualrole( $role );
        }
        $parts  = explode( ',', $role );
        $mapped = [];
        foreach ( $parts as $part ) {
            $mapped[] = self::mapIndividualRole( $part );
        }

        return implode( ',', $mapped );
    }

    public static function mapDietaryRestrictions( string $restrictions ): string {
        if ( empty( $restrictions ) ) {
            return $restrictions;
        }
        if ( ! str_contains( $restrictions, ',' ) ) {
            return self::mapIndividualRestriction( $restrictions );
        }
        $parts  = explode( ',', $restrictions );
        $mapped = [];
        foreach ( $parts as $part ) {
            $mapped[] = self::mapIndividualRestriction( $part );
        }

        return implode( ',', $mapped );
    }

    private static function mapIndividualRole( string $role ): string {
        $role = strtolower( $role );

        return match ( $role ) {
            '',
            'president',
            'technology',
            'vp',
            'marketing',
            'membership',
            'outreach',
            'evangelization',
            'operations',
            'finance', => $role,
            default => 'other-e6e4daf1'
        };
    }

    private static function mapIndividualRestriction( string $restriction ): string {
        $restriction = str( str_replace( '/', '', strtolower( $restriction ) ) )->trim()->value();

        return match ( $restriction ) {
            'glutenwheat',
            'tree nuts',
            'dairy',
            'legumespeanuts',
            'vegetarian' => $restriction,
            'none', '' => '',
        };
    }

    public static function isIndustry( string $param ): bool {
        return match ( $param ) {
            'Non-profit', 'Operations & Logistics', 'Real Estate', 'Software Developer',
            'Software Engineer', 'Television & Media', 'Marketing & Advertising', 'Legal', 'Insurance',
            'Information Technology', 'Human Resources', 'Healthcare', 'Health Care', 'Government',
            'Finance/Accounting', 'Financial Planning', 'Fashion & Design', 'Engineering', 'Energy',
            'Education', 'Communications' => true,
            default => false,
        };
    }

    public static function mapChapterSelection( string $chapter ): string {
        if ( str_contains( $chapter, 'AT LARGE (No YCP Chapter in My City Currently)' ) ) {
            return self::glueUpSlugify( 'AT LARGE (No Chapter Near Me)' );
        }

        return self::glueUpSlugify( $chapter );

    }
}
