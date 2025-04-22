<?php

return [
    'file-prefix' => 'temaki',
    'logger' => [
        'enabled' => false,
        'level' => 'debug',
    ],
    'mode' => env('TEMAKI_MODE', 's3'), // s3, local, memory
    'path' => 'temaki',
    'force_local_path' => false, // Only works for local mode
];
