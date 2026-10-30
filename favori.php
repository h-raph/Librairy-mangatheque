<?php

require __DIR__ . '/src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirection('index.php');
}

csrf_verifier();

$favori = oeuvre_basculer_favori((int) ($_POST['id'] ?? 0));

// Appel depuis favori.js : réponse JSON, pas de rechargement
if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
    header('Content-Type: application/json; charset=utf-8');
    if ($favori === null) {
        http_response_code(404);
    }
    echo json_encode(['favori' => $favori]);
    exit;
}

// Sans JavaScript : retour à la page d'origine (uniquement une page du site)
$retour = (string) ($_POST['retour'] ?? '');
redirection(preg_match('/^(index|manga)\.php(\?[^\s]*)?$/', $retour) ? $retour : 'index.php');
