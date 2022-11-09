<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain'   => env( 'MAILGUN_DOMAIN' ),
        'secret'   => env( 'MAILGUN_SECRET' ),
        'endpoint' => env( 'MAILGUN_ENDPOINT', 'api.mailgun.net' ),
        'scheme'   => 'https',
    ],

    'postmark' => [
        'token' => env( 'POSTMARK_TOKEN' ),
    ],

    'ses'    => [
        'key'    => env( 'AWS_ACCESS_KEY_ID' ),
        'secret' => env( 'AWS_SECRET_ACCESS_KEY' ),
        'region' => env( 'AWS_DEFAULT_REGION', 'us-east-1' ),
    ],
    'glueup' => [
        'key'      => env( 'GLUE_UP_KEY' ),
        'account'  => env( 'GLUE_UP_ACCOUNT' ),
        'version'  => env( 'GLUE_UP_VERSION' ),
        'url'      => env( 'GLUE_UP_URL' ),
        'org_id'   => env( 'GLUE_UP_ORG_ID' ),
        'tenant'   => env( 'GLUE_UP_TENANT' ),
        'chapters' => [
            'YCP - Atlanta'          => 5133,
            'YCP - Austin'           => 5134,
            'YCP - Boston'           => 5135,
            'YCP - Chicago'          => 5136,
            'YCP - Cincinnati'       => 5137,
            'YCP - Cleveland'        => 5138,
            'YCP - Columbus'         => 5139,
            'YCP - Dallas'           => 5140,
            'YCP - Denver'           => 5141,
            'YCP - Detroit'          => 5142,
            'YCP - Fairfield County' => 5143,
            'YCP - Fort Worth'       => 5144,
            'YCP - Houston'          => 5145,
            'YCP - Jacksonville'     => 5146,
            'YCP - Kansas City'      => 51347,
            'YCP - Los Angeles'      => 5148,
            'YCP - Louisville'       => 5149,
            'YCP - Nashville'        => 5150,
            'YCP - New Orleans'      => 5151,
            'YCP - New York City'    => 5152,
            'YCP - Omaha'            => 5153,
            'YCP - Orange County'    => 5154,
            'YCP - Orlando'          => 5155,
            'YCP - Philadelphia'     => 5156,
            'YCP - Phoenix'          => 5157,
            'YCP - Portland'         => 5158,
            'YCP - San Antonio'      => 5159,
            'YCP - San Diego'        => 5160,
            'YCP - Silicon Valley'   => 5161,
            'YCP - St Louis'         => 5162,
        ]
    ]

];
