<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // jwt settings
        "jwt" => [
            'secret' => '6Fyo0lrlT5rqCgjtf7AFCUXJzcyCLjILF9oxUSr16O9VqsB3yvzOZdyaEhVpsZ3'
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
		
		// Database connection settings
        "db" => [
            "host" => "clouddb.c0rw40cy3nwb.us-east-2.rds.amazonaws.com",
            "dbname" => "mainDB",
            "user" => "RBHHC4N9B",
            "pass" => "#QZRNw6ti3F.gI3e9lscKi"
        ],
		
    ],
];
