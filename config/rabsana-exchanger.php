<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rabsana-exchanger Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Rabsana-exchanger will be accessible from. If the
    | setting is null, Rabsana-exchanger will reside under the same domain as the
    | application. Otherwise, this value will be used as the subdomain.
    |
    */

    'domain' => env('RABSANA_EXCHANGER_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Rabsana-exchanger Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Rabsana-exchanger will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('RABSANA_EXCHANGER_PATH', 'rabsana-exchanger'),

    /*
    |--------------------------------------------------------------------------
    | Rabsana-exchanger Admin Api middleware
    |--------------------------------------------------------------------------
    |
    | Here you can add the middlewares for public and private routes.
    | for example you can set the auth:api middleware to private routes to check
    | the user is authenticated or not
    */

    'adminApiMiddlewares' => [
        'group'  => 'web', // web or api
        'public' => [],
        'private' => []
    ],

    /*
    |--------------------------------------------------------------------------
    | Rabsana-exchanger  Api middleware
    |--------------------------------------------------------------------------
    |
    | Here you can add the middlewares for public and private routes.
    | for example you can set the auth:api middleware to private routes to check
    | the user is authenticated or not
    */

    'apiMiddlewares' => [
        'group' => 'api',  // web or api
        'public' => [],
        'private' => []
    ],

    /*
    |--------------------------------------------------------------------------
    | Rabsana-exchanger  views config
    |--------------------------------------------------------------------------
    |
    | for showing the views you can determine these configs or you can publish
    | the package views
    |
    */

    'views' => [
        'admin' => [
            'extends'           => 'rabsana-exchanger::admin.master',
            'content-section'   => 'content',
            'title-section'     => 'title',
            'scripts-stack'     => 'scripts',
            'styles-stack'      => 'styles'
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Rabsana-exchanger  exchanger config
    |--------------------------------------------------------------------------
    |
    | Here you can determine the exchanger and config the exchangers
    |
    */

    'exchanger'                 => 'binance',


    'binance'                   => [
        'apiKey'                => '',
        'secretKey'             => '',
    ],

    'coinex'                    => [
        'accessId'              => '',
        'secretKey'             => ''
    ],

    'kucoin'                    => [
        'passPhrase'            => '',
        'apiKey'                => '',
        'secretKey'             => ''
    ],

];
