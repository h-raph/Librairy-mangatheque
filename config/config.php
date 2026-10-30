<?php

// Identifiants par défaut de MAMP (MySQL sur le port 8889, root / root)
return [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 8889,
        'name'    => 'manga_tracker',
        'user'    => 'root',
        'pass'    => 'root',
        'charset' => 'utf8mb4',
    ],
    'anilist' => [
        'endpoint'        => 'https://graphql.anilist.co',
        'cache_recherche' => 60 * 60 * 24,      // 1 jour
        'cache_media'     => 60 * 60 * 24 * 7,  // 7 jours
    ],
];
