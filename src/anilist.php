<?php

/** Champs GraphQL demandés pour chaque œuvre. */
const ANILIST_CHAMPS = '
    id
    title { romaji english native }
    description(asHtml: false)
    coverImage { extraLarge large }
    genres
    status
    countryOfOrigin
    chapters
    startDate { year }
';

function anilist_requete(string $query, array $variables = []): array
{
    $ch = curl_init(config()['anilist']['endpoint']);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_POSTFIELDS     => json_encode(['query' => $query, 'variables' => $variables]),
    ]);

    $reponse = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($reponse === false) {
        throw new RuntimeException('AniList est injoignable : ' . curl_error($ch));
    }
    if ($code === 429) {
        throw new RuntimeException('Trop de requêtes vers AniList, réessaie dans une minute.');
    }

    $json = json_decode($reponse, true);

    // AniList répond 404 avec data.Media = null quand l'id n'existe pas
    if ($code === 404 && array_key_exists('data', $json ?? [])) {
        return $json['data'] ?? [];
    }
    if ($code !== 200 || !isset($json['data'])) {
        throw new RuntimeException($json['errors'][0]['message'] ?? "Erreur AniList (HTTP $code).");
    }

    return $json['data'];
}

/* ---------- Cache en base ---------- */

function cache_lire(string $cle, int $ttl): mixed
{
    $requete = db()->prepare(
        'SELECT donnees FROM anilist_cache WHERE cle = ? AND cree_le > (NOW() - INTERVAL ? SECOND)'
    );
    $requete->execute([$cle, $ttl]);
    $donnees = $requete->fetchColumn();

    return $donnees === false ? null : json_decode($donnees, true);
}

function cache_ecrire(string $cle, mixed $donnees): void
{
    db()->prepare(
        'INSERT INTO anilist_cache (cle, donnees, cree_le) VALUES (?, ?, NOW())
         ON DUPLICATE KEY UPDATE donnees = VALUES(donnees), cree_le = NOW()'
    )->execute([$cle, json_encode($donnees, JSON_UNESCAPED_UNICODE)]);
}

/* ---------- Appels AniList ---------- */

/** Recherche des œuvres par titre (résultats normalisés). */
function anilist_rechercher(string $terme): array
{
    $cle = 'recherche:' . md5(mb_strtolower(trim($terme)));
    $medias = cache_lire($cle, config()['anilist']['cache_recherche']);

    if ($medias === null) {
        $query = 'query ($search: String) {
            Page(perPage: 10) {
                media(search: $search, type: MANGA, sort: SEARCH_MATCH) {' . ANILIST_CHAMPS . '}
            }
        }';
        $medias = anilist_requete($query, ['search' => $terme])['Page']['media'] ?? [];

        cache_ecrire($cle, $medias);
        // Chaque fiche est aussi mise en cache : l'ajout ne refera pas d'appel
        foreach ($medias as $media) {
            cache_ecrire('media:' . $media['id'], $media);
        }
    }

    return array_map('anilist_normaliser', $medias);
}

/** Récupère une œuvre par son id AniList (normalisée), ou null si elle n'existe pas. */
function anilist_media(int $id, bool $forcer = false): ?array
{
    $cle = 'media:' . $id;
    $media = $forcer ? null : cache_lire($cle, config()['anilist']['cache_media']);

    if ($media === null) {
        $query = 'query ($id: Int) { Media(id: $id, type: MANGA) {' . ANILIST_CHAMPS . '} }';
        $media = anilist_requete($query, ['id' => $id])['Media'] ?? null;

        if ($media === null) {
            return null;
        }
        cache_ecrire($cle, $media);
    }

    return anilist_normaliser($media);
}

/** Convertit une réponse AniList au format des colonnes de la table oeuvres. */
function anilist_normaliser(array $media): array
{
    $titres = $media['title'];
    $titre = $titres['english'] ?: $titres['romaji'];
    $alternatif = $titres['romaji'] !== $titre ? $titres['romaji'] : $titres['native'];

    return [
        'anilist_id'         => $media['id'],
        'titre'              => $titre,
        'titre_alternatif'   => $alternatif,
        'type'               => match ($media['countryOfOrigin'] ?? 'JP') {
            'KR'             => 'manhwa',
            'CN', 'TW', 'HK' => 'manhua',
            default          => 'manga',
        },
        'resume'             => anilist_nettoyer_resume($media['description'] ?? ''),
        'cover_url'          => $media['coverImage']['extraLarge'] ?? $media['coverImage']['large'] ?? null,
        'statut_publication' => $media['status'] ?? null,
        'chapitres_total'    => $media['chapters'] ?? null,
        'annee'              => $media['startDate']['year'] ?? null,
        'genres'             => $media['genres'] ?? [],
    ];
}

function anilist_nettoyer_resume(string $texte): string
{
    $texte = preg_replace('/<br\s*\/?>/i', "\n", $texte);
    $texte = html_entity_decode(strip_tags($texte), ENT_QUOTES, 'UTF-8');

    return trim(preg_replace("/\n{3,}/", "\n\n", $texte));
}

/** Télécharge la cover en local pour ne plus dépendre du CDN d'AniList. */
function telecharger_cover(?string $url, int $anilistId): ?string
{
    if (!$url) {
        return null;
    }

    $dossier = __DIR__ . '/../assets/covers';
    if (!is_dir($dossier) && !mkdir($dossier, 0775, true)) {
        return null;
    }

    $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)) ?: 'jpg';
    $fichier = $anilistId . '.' . $extension;

    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_FOLLOWLOCATION => true]);
    $image = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($image === false || $code !== 200 || file_put_contents("$dossier/$fichier", $image) === false) {
        return null;
    }

    return 'assets/covers/' . $fichier;
}
