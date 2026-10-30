<?php

const TYPES = [
    'manga'  => 'Manga',
    'manhwa' => 'Manhwa',
    'manhua' => 'Manhua',
];

const LANGUES = [
    'fr' => 'Français',
    'en' => 'Anglais',
    'es' => 'Espagnol',
    'ja' => 'Japonais',
    'ko' => 'Coréen',
    'zh' => 'Chinois',
];

const STATUTS_LECTURE = [
    'en_cours'  => 'En cours',
    'a_lire'    => 'À lire',
    'termine'   => 'Terminé',
    'en_pause'  => 'En pause',
    'abandonne' => 'Abandonné',
];

const STATUTS_PUBLICATION = [
    'FINISHED'         => 'Terminé',
    'RELEASING'        => 'En cours de parution',
    'NOT_YET_RELEASED' => 'À paraître',
    'CANCELLED'        => 'Annulé',
    'HIATUS'           => 'En pause',
];

const TRIS = [
    'recent' => 'Plus récent',
    'ancien' => 'Plus ancien',
    'az'     => 'A → Z',
    'za'     => 'Z → A',
];

const GENRES_FR = [
    'Action'        => 'Action',
    'Adventure'     => 'Aventure',
    'Comedy'        => 'Comédie',
    'Drama'         => 'Drame',
    'Ecchi'         => 'Ecchi',
    'Fantasy'       => 'Fantasy',
    'Horror'        => 'Horreur',
    'Mahou Shoujo'  => 'Magical girl',
    'Mecha'         => 'Mecha',
    'Music'         => 'Musique',
    'Mystery'       => 'Mystère',
    'Psychological' => 'Psychologique',
    'Romance'       => 'Romance',
    'Sci-Fi'        => 'Science-fiction',
    'Slice of Life' => 'Tranche de vie',
    'Sports'        => 'Sport',
    'Supernatural'  => 'Surnaturel',
    'Thriller'      => 'Thriller',
];

function config(): array
{
    static $config = null;
    return $config ??= require __DIR__ . '/../config/config.php';
}

/** Échappe une valeur pour l'affichage HTML. */
function e(?string $valeur): string
{
    return htmlspecialchars($valeur ?? '', ENT_QUOTES, 'UTF-8');
}

function libelle(array $liste, ?string $cle): string
{
    return $liste[$cle] ?? (string) $cle;
}

function genre_fr(string $genre): string
{
    return GENRES_FR[$genre] ?? $genre;
}

/** Transforme une valeur (ex. "en_cours") en modificateur BEM ("en-cours"). */
function modificateur(string $valeur): string
{
    return str_replace('_', '-', strtolower($valeur));
}

function cover(array $oeuvre): string
{
    return $oeuvre['cover_locale'] ?: ($oeuvre['cover_url'] ?: 'assets/img/placeholder.svg');
}

function redirection(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flashs(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_champ(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_verifier(): void
{
    if (!hash_equals(csrf_token(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Jeton de sécurité invalide, recharge la page.');
    }
}
