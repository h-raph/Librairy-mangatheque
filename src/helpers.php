<?php

const TYPES = [
    'manga'  => 'Manga',
    'manhwa' => 'Manhwa',
    'manhua' => 'Manhua',
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
    'az'     => 'A → Z',
    'za'     => 'Z → A',
    'recent' => 'Plus récent',
    'ancien' => 'Plus ancien',
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

/** Ajoute la date de modification du fichier à son URL pour que le navigateur recharge les nouvelles versions. */
function asset(string $chemin): string
{
    $fichier = __DIR__ . '/../' . $chemin;
    return is_file($fichier) ? $chemin . '?v=' . filemtime($fichier) : $chemin;
}

/** Cover locale si le fichier existe, sinon celle d'AniList, sinon l'image par défaut. */
function cover(array $oeuvre): string
{
    if ($oeuvre['cover_locale'] && is_file(__DIR__ . '/../' . $oeuvre['cover_locale'])) {
        return $oeuvre['cover_locale'];
    }

    return $oeuvre['cover_url'] ?: 'assets/img/placeholder.svg';
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
