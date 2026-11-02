<?php

require __DIR__ . '/src/bootstrap.php';

$valeurs = [
    'anilist_id'      => '',
    'titre'           => '',
    'type'            => 'manga',
    'statut_lecture'  => 'en_cours',
    'chapitre_actuel' => 0,
    'physique'        => 0,
    'volume_actuel'   => 0,
    'lien'            => '',
    'resume'          => '',
    'cover_url'       => '',
];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifier();

    $valeurs = array_map(fn ($v) => is_string($v) ? trim($v) : '', array_intersect_key($_POST, $valeurs)) + $valeurs;
    $valeurs['chapitre_actuel'] = max(0, (int) $valeurs['chapitre_actuel']);
    $valeurs['volume_actuel'] = max(0, (int) $valeurs['volume_actuel']);
    $valeurs['physique'] = $valeurs['physique'] ? 1 : 0;
    $anilistId = (int) $valeurs['anilist_id'];

    if (!isset(TYPES[$valeurs['type']])) {
        $erreurs[] = 'Type invalide.';
    }
    if (!isset(STATUTS_LECTURE[$valeurs['statut_lecture']])) {
        $erreurs[] = 'Statut de lecture invalide.';
    }
    if ($valeurs['lien'] !== '' && !filter_var($valeurs['lien'], FILTER_VALIDATE_URL)) {
        $erreurs[] = 'Le lien de lecture doit être une URL valide.';
    }
    if ($valeurs['cover_url'] !== '' && !filter_var($valeurs['cover_url'], FILTER_VALIDATE_URL)) {
        $erreurs[] = "L'URL de la cover doit être une URL valide.";
    }
    if (!$anilistId && $valeurs['titre'] === '') {
        $erreurs[] = 'Choisis une œuvre AniList ou saisis au moins un titre.';
    }

    $media = null;
    $genres = [];

    if ($anilistId && !$erreurs) {
        $existante = oeuvres_ids_par_anilist([$anilistId]);
        if ($existante) {
            flash('info', 'Cette œuvre est déjà dans ta bibliothèque.');
            redirection('manga.php?id=' . $existante[$anilistId]);
        }

        try {
            $media = anilist_media($anilistId);
            if ($media === null) {
                $erreurs[] = 'Œuvre introuvable sur AniList.';
            }
        } catch (RuntimeException $erreur) {
            $erreurs[] = $erreur->getMessage();
        }
    }

    if (!$erreurs) {
        $oeuvre = [
            'titre'           => $valeurs['titre'],
            'type'            => $valeurs['type'],
            'statut_lecture'  => $valeurs['statut_lecture'],
            'chapitre_actuel' => $valeurs['chapitre_actuel'],
            'physique'        => $valeurs['physique'],
            'volume_actuel'   => $valeurs['volume_actuel'],
            'lien'            => $valeurs['lien'] ?: null,
            'resume'          => $valeurs['resume'] ?: null,
            'cover_url'       => $valeurs['cover_url'] ?: null,
        ];

        if ($media) {
            $genres = $media['genres'];
            $oeuvre = [
                'titre'              => $valeurs['titre'] ?: $media['titre'],
                'anilist_id'         => $media['anilist_id'],
                'titre_alternatif'   => $media['titre_alternatif'],
                'resume'             => $media['resume'],
                'cover_url'          => $media['cover_url'],
                'cover_locale'       => telecharger_cover($media['cover_url'], $media['anilist_id']),
                'statut_publication' => $media['statut_publication'],
                'chapitres_total'    => $media['chapitres_total'],
                'volumes_total'      => $media['volumes_total'],
                'annee'              => $media['annee'],
                'anilist_synchro_le' => date('Y-m-d H:i:s'),
            ] + $oeuvre;
        }

        $id = oeuvre_creer($oeuvre, $genres);
        flash('succes', "« {$oeuvre['titre']} » a été ajouté à ta bibliothèque.");
        redirection('manga.php?id=' . $id);
    }
}

$titrePage = 'Ajouter une lecture';
$pageActive = 'ajouter';
$scripts = ['assets/js/ajouter.js'];
require __DIR__ . '/templates/header.php';
?>

<header class="entete-page">
    <span class="entete-page__kanji" aria-hidden="true">記録</span>
    <div class="entete-page__textes">
        <h1 class="entete-page__titre">Ajouter une lecture</h1>
        <p class="entete-page__compteur">Cherche l'œuvre sur AniList pour récupérer automatiquement le résumé, la cover, les genres et le statut.</p>
    </div>
</header>

<?php foreach ($erreurs as $erreur): ?>
    <p class="alerte alerte--erreur" role="alert"><?= e($erreur) ?></p>
<?php endforeach; ?>

<div class="l-ajout">
    <section class="recherche" data-recherche>
        <label class="recherche__label" for="recherche">Rechercher sur AniList</label>
        <div class="recherche__barre">
            <input class="recherche__champ" type="search" id="recherche" placeholder="Ex. Solo Leveling, One Piece…" autocomplete="off" data-recherche-champ>
            <span class="recherche__chargement" aria-hidden="true"></span>
        </div>
        <p class="recherche__message is-hidden" data-recherche-message></p>
        <ul class="recherche__resultats" data-recherche-resultats></ul>
    </section>

    <form class="formulaire" method="post" action="ajouter.php">
        <?= csrf_champ() ?>
        <input type="hidden" name="anilist_id" value="<?= e((string) $valeurs['anilist_id']) ?>" data-champ-anilist>

        <div class="apercu is-hidden" data-apercu>
            <img class="apercu__image" src="" alt="" data-apercu-image>
            <div class="apercu__infos">
                <p class="apercu__titre" data-apercu-titre></p>
                <p class="apercu__meta" data-apercu-meta></p>
                <p class="apercu__resume" data-apercu-resume></p>
            </div>
            <button class="bouton bouton--petit bouton--secondaire apercu__retirer" type="button" data-apercu-retirer>Retirer</button>
        </div>

        <div class="formulaire__groupe">
            <label class="formulaire__label" for="titre">Titre</label>
            <input class="formulaire__champ" type="text" id="titre" name="titre" value="<?= e($valeurs['titre']) ?>" data-champ-titre>
            <p class="formulaire__aide">Rempli automatiquement depuis AniList, modifiable.</p>
        </div>

        <div class="formulaire__groupe">
            <label class="formulaire__label" for="type">Type</label>
            <select class="formulaire__champ" id="type" name="type" data-champ-type>
                <?php foreach (TYPES as $valeur => $libelle): ?>
                    <option value="<?= e($valeur) ?>" <?= $valeurs['type'] === $valeur ? 'selected' : '' ?>><?= e($libelle) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="formulaire__ligne">
            <div class="formulaire__groupe">
                <label class="formulaire__label" for="statut_lecture">Statut de lecture</label>
                <select class="formulaire__champ" id="statut_lecture" name="statut_lecture">
                    <?php foreach (STATUTS_LECTURE as $valeur => $libelle): ?>
                        <option value="<?= e($valeur) ?>" <?= $valeurs['statut_lecture'] === $valeur ? 'selected' : '' ?>><?= e($libelle) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="formulaire__groupe <?= $valeurs['physique'] ? 'is-hidden' : '' ?>" data-progression-chapitre>
                <label class="formulaire__label" for="chapitre_actuel">Chapitre actuel</label>
                <input class="formulaire__champ" type="number" min="0" id="chapitre_actuel" name="chapitre_actuel" value="<?= (int) $valeurs['chapitre_actuel'] ?>">
            </div>
            <div class="formulaire__groupe <?= $valeurs['physique'] ? '' : 'is-hidden' ?>" data-progression-volume>
                <label class="formulaire__label" for="volume_actuel">Volume actuel</label>
                <input class="formulaire__champ" type="number" min="0" id="volume_actuel" name="volume_actuel" value="<?= (int) $valeurs['volume_actuel'] ?>">
            </div>
        </div>

        <label class="formulaire__option">
            <input class="formulaire__case" type="checkbox" name="physique" value="1" <?= $valeurs['physique'] ? 'checked' : '' ?> data-champ-physique>
            Je l'ai en physique (suivi par volume)
        </label>

        <div class="formulaire__groupe">
            <label class="formulaire__label" for="lien">Lien de lecture</label>
            <input class="formulaire__champ" type="url" id="lien" name="lien" value="<?= e($valeurs['lien']) ?>" placeholder="https://…">
        </div>

        <details class="formulaire__manuel" <?= $valeurs['resume'] || $valeurs['cover_url'] ? 'open' : '' ?>>
            <summary class="formulaire__manuel-titre">Saisie manuelle (si l'œuvre n'est pas sur AniList)</summary>
            <div class="formulaire__groupe">
                <label class="formulaire__label" for="cover_url">URL de la cover</label>
                <input class="formulaire__champ" type="url" id="cover_url" name="cover_url" value="<?= e($valeurs['cover_url']) ?>">
            </div>
            <div class="formulaire__groupe">
                <label class="formulaire__label" for="resume">Résumé</label>
                <textarea class="formulaire__champ formulaire__champ--texte" id="resume" name="resume" rows="5"><?= e($valeurs['resume']) ?></textarea>
            </div>
        </details>

        <div class="formulaire__actions">
            <button class="bouton bouton--principal" type="submit">Enregistrer</button>
            <a class="bouton bouton--secondaire" href="index.php">Annuler</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
