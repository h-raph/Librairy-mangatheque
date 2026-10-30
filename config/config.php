<?php

// Identifiants par défaut de MAMP (MySQL sur le port 8889, root / root)
$config = [
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

// Sur alwaysdata, les identifiants de config.alwaysdata.php remplacent ceux de MAMP
$fichierEnLigne = __DIR__ . '/config.alwaysdata.php';
if (str_ends_with($_SERVER['HTTP_HOST'] ?? '', '.alwaysdata.net') && is_file($fichierEnLigne)) {
    $config = array_replace_recursive($config, require $fichierEnLigne);
}

return $config;
