<?php

return [
    // relative
    'data_dir' => 'data',
    'repo_id' => 'default1',
    'repo_name' => 'Custom Project Repo',

    'repo_keys' => [
        'mykey1' => 'READ',
        'mykey2' => 'WRITE',
        'mykey3' => 'ADMIN',
    ],

    // fetching projects available for anybody 
    //'read_without_key' => false,
    
    // all manipulations on repo disabled
    //'read_only' => true,
];
