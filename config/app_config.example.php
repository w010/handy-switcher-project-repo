<?php

// application system settings (not related to Repo functionality itself)

return [
    'repo' => [

        // authorization / access level
        // keys syntax is: 'access key' => permission (can be: READ, WRITE, ADMIN)
        'repo_keys' => [

            // EXAMPLE KEYS - YOU SHOULD REMOVE THEM!

            'mykey1' => 'READ',
            'mykey2' => 'WRITE',
            'mykey3' => 'ADMIN',
        ],

        'repo_name' => 'Custom Project Repo',
        // relative
        'data_dir' => 'data',
        'repo_id' => 'default1',


        // PUBLIC REPO READ - fetching projects available for EVERYBODY
        // CONSIDER DEACTIVATING
        'read_without_key' => true,

        // disable all manipulation on repo
        'read_only' => false,

        // Demo mode, which uses fake Write auth for dummy push action
        // used for presentation
        'DEMO_MODE' => false,
    ],

    // base tag, if needed
    'baseHref' => '',

    // not used currently
    /*'dbAuth' => [
        'user' => '',
        'password' => '',
        'host' => '',
        'dbname' => ''
    ],*/

    // Display some debug & additional dev info
    'DEV' => false,

];
