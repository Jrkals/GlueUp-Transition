<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GlueUp {
    private static $DEBUG_MODE = false;

    public static function get( $operation, $token = '' ) {
        return self::doCall( 'get', $operation, '', $token );
    }

    public static function post( $operation, $params = '', $token = '' ) {
        return self::doCall( 'post', $operation, $params, $token );
    }

    public static function put( $operation, $params = '', $token = '' ) {
        return self::doCall( 'put', $operation, $params, $token );
    }

    public static function delete( $operation, $params = '', $token = '' ) {
        return self::doCall( 'delete', $operation, $params, $token );
    }

    private static function doCall( $method, $operation, $params = '', $token = '' ) {
        $timer = self::$DEBUG_MODE ? microtime( true ) : 0;
        $curl  = curl_init();

        curl_setopt( $curl, CURLOPT_URL, config( 'services.glueup.url' ) . '/' . $operation );

        switch ( strtolower( $method ) ) {
            case 'post':
                curl_setopt( $curl, CURLOPT_POST, 1 );
                break;
            case 'put':
                curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'PUT' );
                break;
            case 'delete':
                curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'DELETE' );
                break;
        }

        $ts   = time() * 1000;
        $hash = strtoupper( $method ) . config( 'services.glueup.account' ) . config( 'services.glueup.version' ) . $ts;
        $d    = hash_hmac( 'sha256', $hash, config( 'services.glueup.key' ) );
        $a    = 'a:d=' . $d . ';v=' . config( 'services.glueup.version' ) . ';k=' . config( 'services.glueup.account' ) . ';ts=' . $ts;

        $arrHeader = array(
            'Accept:application/json',
            'Cache-Control:no-cache',
            'tenantId:' . config( 'services.glueup.tenant' ),
            $a
        );

        if ( $params != '' ) {
            curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode( $params ) );
            $arrHeader[] = 'Content-type:application/json;charset=UTF-8';
        }

        if ( $token != '' ) {
            $arrHeader[] = 'token:' . $token;
        }
        curl_setopt( $curl, CURLINFO_HEADER_OUT, true ); //added by justin
        curl_setopt( $curl, CURLOPT_HTTPHEADER, $arrHeader );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );

        $execResult = curl_exec( $curl );
        echo curl_getinfo( $curl, CURLINFO_HEADER_OUT ); //added by justin
        $info = curl_getinfo( $curl );

        $return = array( 'code' => $info['http_code'], 'data' => json_decode( $execResult, true ) );

        curl_close( $curl );

        if ( self::$DEBUG_MODE ) {
            error_log( '-------------------- ' . strtoupper( $method ) . ': ' . $operation . ' in ' . ( ( microtime( true ) - $timer ) * 1000 ) . 'ms --------------------' );
            error_log( 'RESULT: ' . json_encode( $return ) );
            if ( $params != '' ) {
                error_log( 'PARAMS: ' . json_encode( $params ) );
            }
        }

        return $return;
    }
}
