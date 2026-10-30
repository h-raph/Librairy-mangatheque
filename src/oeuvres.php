<?php

const COLONNES_OEUVRE = [
    'anilist_id', 'titre', 'titre_alternatif', 'type', 'resume',
    'cover_url', 'cover_locale', 'statut_publication', 'statut_lecture', 'favori',
    'chapitre_actuel', 'chapitres_total', 'lien', 'annee', 'anilist_synchro_le',
];

const ORDRES_TRI = [
    'az'     => 'o.titre ASC',
    'za'     => 'o.titre DESC',
    'recent' => 'o.cree_le DESC, o.id DESC',
    'ancien' => 'o.cree_le ASC, o.id ASC',
];

/**
 * Liste les œuvres selon les filtres :
 * q (titre), genres (toutes requises), type, statut, favoris, tri.
 */
function oeuvres_lister(array $filtres): array
{
    $conditions = [];
    $parametres = [];

    if ($filtres['q'] !== '') {
        $conditions[] = '(o.titre LIKE ? OR o.titre_alternatif LIKE ?)';
        $parametres[] = '%' . $filtres['q'] . '%';
        $parametres[] = '%' . $filtres['q'] . '%';
    }
    if ($filtres['type'] !== '') {
        $conditions[] = 'o.type = ?';
        $parametres[] = $filtres['type'];
    }
    if ($filtres['statut'] !== '') {
        $conditions[] = 'o.statut_lecture = ?';
        $parametres[] = $filtres['statut'];
    }
    if ($filtres['favoris']) {
        $conditions[] = 'o.favori = 1';
    }
    if ($filtres['genres']) {
        $marqueurs = implode(', ', array_fill(0, count($filtres['genres']), '?'));
        $conditions[] = "o.id IN (
            SELECT og2.oeuvre_id FROM oeuvre_genre og2
            JOIN genres g2 ON g2.id = og2.genre_id
            WHERE g2.nom IN ($marqueurs)
            GROUP BY og2.oeuvre_id
            HAVING COUNT(DISTINCT g2.id) = " . count($filtres['genres']) . '
        )';
        array_push($parametres, ...$filtres['genres']);
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $ordre = ORDRES_TRI[$filtres['tri']] ?? ORDRES_TRI['az'];

    $requete = db()->prepare("
        SELECT o.*, GROUP_CONCAT(g.nom ORDER BY g.nom SEPARATOR '|') AS genres
        FROM oeuvres o
        LEFT JOIN oeuvre_genre og ON og.oeuvre_id = o.id
        LEFT JOIN genres g ON g.id = og.genre_id
        $where
        GROUP BY o.id
        ORDER BY $ordre
    ");
    $requete->execute($parametres);

    return array_map('oeuvre_decoder_genres', $requete->fetchAll());
}

function oeuvre_trouver(int $id): ?array
{
    $requete = db()->prepare("
        SELECT o.*, GROUP_CONCAT(g.nom ORDER BY g.nom SEPARATOR '|') AS genres
        FROM oeuvres o
        LEFT JOIN oeuvre_genre og ON og.oeuvre_id = o.id
        LEFT JOIN genres g ON g.id = og.genre_id
        WHERE o.id = ?
        GROUP BY o.id
    ");
    $requete->execute([$id]);
    $oeuvre = $requete->fetch();

    return $oeuvre ? oeuvre_decoder_genres($oeuvre) : null;
}

/** Retourne [anilist_id => id local] pour les ids AniList déjà présents. */
function oeuvres_ids_par_anilist(array $anilistIds): array
{
    if (!$anilistIds) {
        return [];
    }
    $marqueurs = implode(', ', array_fill(0, count($anilistIds), '?'));
    $requete = db()->prepare("SELECT anilist_id, id FROM oeuvres WHERE anilist_id IN ($marqueurs)");
    $requete->execute(array_values($anilistIds));

    return $requete->fetchAll(PDO::FETCH_KEY_PAIR);
}

function oeuvre_creer(array $oeuvre, array $genres): int
{
    $pdo = db();
    $colonnes = array_values(array_intersect(COLONNES_OEUVRE, array_keys($oeuvre)));

    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            'INSERT INTO oeuvres (' . implode(', ', $colonnes) . ')
             VALUES (:' . implode(', :', $colonnes) . ')'
        )->execute(array_intersect_key($oeuvre, array_flip($colonnes)));

        $id = (int) $pdo->lastInsertId();
        genres_synchroniser($id, $genres);
        $pdo->commit();
    } catch (Throwable $erreur) {
        $pdo->rollBack();
        throw $erreur;
    }

    return $id;
}

function oeuvre_modifier(int $id, array $champs): void
{
    $champs = array_intersect_key($champs, array_flip(COLONNES_OEUVRE));
    if (!$champs) {
        return;
    }

    $affectations = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($champs)));
    db()->prepare("UPDATE oeuvres SET $affectations WHERE id = :id")
        ->execute($champs + ['id' => $id]);
}

/** Inverse l'état favori et retourne le nouvel état (null si l'œuvre n'existe pas). */
function oeuvre_basculer_favori(int $id): ?bool
{
    db()->prepare('UPDATE oeuvres SET favori = 1 - favori WHERE id = ?')->execute([$id]);

    $requete = db()->prepare('SELECT favori FROM oeuvres WHERE id = ?');
    $requete->execute([$id]);
    $favori = $requete->fetchColumn();

    return $favori === false ? null : (bool) $favori;
}

function oeuvre_supprimer(int $id): void
{
    db()->prepare('DELETE FROM oeuvres WHERE id = ?')->execute([$id]);
}

function genres_synchroniser(int $oeuvreId, array $genres): void
{
    $pdo = db();
    $pdo->prepare('DELETE FROM oeuvre_genre WHERE oeuvre_id = ?')->execute([$oeuvreId]);

    $creer = $pdo->prepare('INSERT IGNORE INTO genres (nom) VALUES (?)');
    $lier = $pdo->prepare(
        'INSERT IGNORE INTO oeuvre_genre (oeuvre_id, genre_id) SELECT ?, id FROM genres WHERE nom = ?'
    );
    foreach (array_unique($genres) as $genre) {
        $creer->execute([$genre]);
        $lier->execute([$oeuvreId, $genre]);
    }
}

/** Genres présents dans la bibliothèque (pour le filtre). */
function genres_utilises(): array
{
    return db()->query(
        'SELECT DISTINCT g.nom FROM genres g JOIN oeuvre_genre og ON og.genre_id = g.id ORDER BY g.nom'
    )->fetchAll(PDO::FETCH_COLUMN);
}

function oeuvre_decoder_genres(array $oeuvre): array
{
    $oeuvre['genres'] = $oeuvre['genres'] ? explode('|', $oeuvre['genres']) : [];
    return $oeuvre;
}
