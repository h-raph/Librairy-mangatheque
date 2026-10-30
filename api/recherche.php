<?php

require __DIR__ . '/../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$terme = trim((string) ($_GET['q'] ?? ''));

if (mb_strlen($terme) < 2) {
    echo json_encode(['resultats' => []]);
    exit;
}

try {
    $resultats = anilist_rechercher($terme);
} catch (RuntimeException $erreur) {
    http_response_code(502);
    echo json_encode(['erreur' => $erreur->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

// Signale les œuvres déjà présentes dans la bibliothèque
$existantes = oeuvres_ids_par_anilist(array_column($resultats, 'anilist_id'));

$resultats = array_map(fn (array $r) => $r + [
    'oeuvre_id'      => $existantes[$r['anilist_id']] ?? null,
    'type_libelle'   => libelle(TYPES, $r['type']),
    'statut_libelle' => libelle(STATUTS_PUBLICATION, $r['statut_publication']),
    'genres_fr'      => array_map('genre_fr', $r['genres']),
], $resultats);

echo json_encode(['resultats' => $resultats], JSON_UNESCAPED_UNICODE);
