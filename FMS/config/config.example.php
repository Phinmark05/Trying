<?php
/**
 * Copy this file to config.php and fill in YOUR local values.
 * config.php is listed in .gitignore so real credentials are NEVER committed.
 */
return [
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'FMS',
        'user'     => 'fms_user',
        'password' => 'change_me',
        'charset'  => 'utf8mb4',
    ],
    'app' => [
        'name'  => 'Field Application Management System',
        'debug' => true, // set to false in production
    ],
];
