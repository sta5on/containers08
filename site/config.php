<?php

$config = [
    'db' => [
        'path' => getenv('DB_PATH') ?: '/var/www/db/db.sqlite',
    ],
];
