<?php

require __DIR__ . '/src/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$oeuvre = oeuvre_trouver($id);

if (!$oeuvre) {
    flash('erreur', 'Œuvre introuvable.');
    redirection('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifier();

    // On n'avance que le compteur affiché : l'autre garde sa valeur si on rebascule plus tard
    $colonneProgression = $oeuvre['physique'] ? 'volume_actuel' : 'chapitre_actuel';

    switch ($_POST['action'] ?? '') {
        case 'modifier':
            $champs = [
                'titre'           => trim((string) ($_POST['titre'] ?? '')) ?: $oeuvre['titre'],
                'type'            => isset(TYPES[$_POST['type'] ?? '']) ? $_POST['type'] : $oeuvre['type'],
                'statut_lecture'  => isset(STATUTS_LECTURE[$_POST['statut_lecture'] ?? '']) ? $_POST['statut_lecture'] : $oeuvre['statut_lecture'],
                $colonneProgression => max(0, (int) ($_POST[$colonneProgression] ?? 0)),
                'lien'            => filter_var(trim((string) ($_POST['lien'] ?? '')), FILTER_VALIDATE_URL) ?: null,
            ];
            oeuvre_modifier($id, $champs);
            flash('succes', 'Lecture mise à jour.');
            break;

        case 'progression_suivante':
            $suivant = $oeuvre[$colonneProgression] + 1;
            oeuvre_modifier($id, [$colonneProgression => $suivant]);
            flash('succes', ($oeuvre['physique'] ? 'Volume ' : 'Chapitre ') . $suivant . ' enregistré.');
            break;

        case 'basculer_physique':
            oeuvre_modifier($id, ['physique' => $oeuvre['physique'] ? 0 : 1]);
            flash('succes', $oeuvre['physique'] ? 'Œuvre retirée du physique : suivi par chapitre.' : 'Œuvre en physique : suivi par volume.');
            break;

        case 'synchroniser':
            if (!$oeuvre['anilist_id']) {
                break;
            }
            try {
                $media = anilist_media((int) $oeuvre['anilist_id'], true);
                if ($media) {
                    oeuvre_modifier($id, [
                        'titre_alternatif'   => $media['titre_alternatif'],
                        'resume'             => $media['resume'],
                        'cover_url'          => $media['cover_url'],
                        'cover_locale'       => telecharger_cover($media['cover_url'], $media['anilist_id']) ?? $oeuvre['cover_locale'],
                        'statut_publication' => $media['statut_publication'],
                        'chapitres_total'    => $media['chapitres_total'],
                        'volumes_total'      => $media['volumes_total'],
                        'annee'              => $media['annee'],
                        'anilist_synchro_le' => date('Y-m-d H:i:s'),
                    ]);
                    genres_synchroniser($id, $media['genres']);
                    flash('succes', 'Informations mises à jour depuis AniList.');
                }
            } catch (RuntimeException $erreur) {
                flash('erreur', $erreur->getMessage());
            }
            break;

        case 'supprimer':
            oeuvre_supprimer($id);
            flash('succes', "« {$oeuvre['titre']} » a été supprimé.");
            redirection('index.php');
    }

    redirection('manga.php?id=' . $id);
}

$titrePage = $oeuvre['titre'];
$pageActive = '';
$scripts = ['assets/js/favori.js'];
require __DIR__ . '/templates/header.php';
?>

<a class="lien-retour" href="index.php">← Retour à la bibliothèque</a>

<article class="fiche">
    <div class="fiche__couverture">
        <div class="fiche__cadre">
            <img class="fiche__image" src="<?= e(cover($oeuvre)) ?>" alt="Cover de <?= e($oeuvre['titre']) ?>">
        </div>
        <?php $classeFavori = 'favori--fiche'; require __DIR__ . '/templates/partials/bouton-favori.php'; ?>
        <form method="post" class="fiche__action">
            <?= csrf_champ() ?>
            <input type="hidden" name="action" value="basculer_physique">
            <button class="bouton bouton--secondaire bouton--large <?= $oeuvre['physique'] ? 'is-active' : '' ?>" type="submit" aria-pressed="<?= $oeuvre['physique'] ? 'true' : 'false' ?>">
                <?= $oeuvre['physique'] ? '✓ En physique' : 'Je l\'ai en physique' ?>
            </button>
        </form>
        <?php if ($oeuvre['lien']): ?>
            <a class="bouton bouton--principal bouton--large" href="<?= e($oeuvre['lien']) ?>" target="_blank" rel="noopener">
                <?= $oeuvre['physique'] ? 'Lire en ligne' : 'Lire le chapitre ' . ((int) $oeuvre['chapitre_actuel'] + 1) ?>
            </a>
        <?php endif; ?>
        <form method="post" class="fiche__action">
            <?= csrf_champ() ?>
            <input type="hidden" name="action" value="progression_suivante">
            <button class="bouton bouton--secondaire bouton--large" type="submit"><?= $oeuvre['physique'] ? '+1 volume lu' : '+1 chapitre lu' ?></button>
        </form>
    </div>

    <div class="fiche__infos">
        <div class="fiche__badges">
            <span class="badge badge--<?= e($oeuvre['type']) ?>"><?= e(libelle(TYPES, $oeuvre['type'])) ?></span>
            <span class="badge badge--statut badge--<?= e(modificateur($oeuvre['statut_lecture'])) ?>"><?= e(libelle(STATUTS_LECTURE, $oeuvre['statut_lecture'])) ?></span>
            <?php if ($oeuvre['physique']): ?>
                <span class="badge badge--neutre">Physique</span>
            <?php endif; ?>
        </div>

        <h1 class="fiche__titre"><?= e($oeuvre['titre']) ?></h1>
        <?php if ($oeuvre['titre_alternatif']): ?>
            <p class="fiche__titre-alt"><?= e($oeuvre['titre_alternatif']) ?></p>
        <?php endif; ?>

        <dl class="fiche__details">
            <div class="fiche__detail">
                <dt class="fiche__detail-label">Progression</dt>
                <dd class="fiche__detail-valeur"><?= e(progression($oeuvre)) ?></dd>
            </div>
            <?php if ($oeuvre['statut_publication']): ?>
                <div class="fiche__detail">
                    <dt class="fiche__detail-label">Publication</dt>
                    <dd class="fiche__detail-valeur"><?= e(libelle(STATUTS_PUBLICATION, $oeuvre['statut_publication'])) ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($oeuvre['annee']): ?>
                <div class="fiche__detail">
                    <dt class="fiche__detail-label">Année</dt>
                    <dd class="fiche__detail-valeur"><?= (int) $oeuvre['annee'] ?></dd>
                </div>
            <?php endif; ?>
            <div class="fiche__detail">
                <dt class="fiche__detail-label">Ajouté le</dt>
                <dd class="fiche__detail-valeur"><?= e(date('d/m/Y', strtotime($oeuvre['cree_le']))) ?></dd>
            </div>
        </dl>

        <?php if ($oeuvre['genres']): ?>
            <ul class="fiche__genres">
                <?php foreach ($oeuvre['genres'] as $genre): ?>
                    <li class="fiche__genre"><a class="fiche__genre-lien" href="index.php?genres[]=<?= urlencode($genre) ?>"><?= e(genre_fr($genre)) ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($oeuvre['resume']): ?>
            <h2 class="fiche__sous-titre"><span class="fiche__kanji" aria-hidden="true">物語</span>Résumé</h2>
            <p class="fiche__resume"><?= nl2br(e($oeuvre['resume'])) ?></p>
        <?php endif; ?>

        <?php if ($oeuvre['anilist_id']): ?>
            <form method="post" class="fiche__synchro">
                <?= csrf_champ() ?>
                <input type="hidden" name="action" value="synchroniser">
                <span class="fiche__synchro-texte">
                    Données AniList du <?= e(date('d/m/Y', strtotime($oeuvre['anilist_synchro_le'] ?? $oeuvre['cree_le']))) ?>
                    · <a class="fiche__synchro-lien" href="https://anilist.co/manga/<?= (int) $oeuvre['anilist_id'] ?>" target="_blank" rel="noopener">voir sur AniList</a>
                </span>
                <button class="bouton bouton--petit bouton--secondaire" type="submit">Actualiser</button>
            </form>
        <?php endif; ?>
    </div>
</article>

<section class="panneau">
    <h2 class="panneau__titre"><span class="panneau__kanji" aria-hidden="true">読書</span>Ma lecture</h2>
    <form class="formulaire" method="post">
        <?= csrf_champ() ?>
        <input type="hidden" name="action" value="modifier">

        <div class="formulaire__groupe">
            <label class="formulaire__label" for="titre">Titre</label>
            <input class="formulaire__champ" type="text" id="titre" name="titre" value="<?= e($oeuvre['titre']) ?>" required>
        </div>

        <div class="formulaire__groupe">
            <label class="formulaire__label" for="type">Type</label>
            <select class="formulaire__champ" id="type" name="type">
                <?php foreach (TYPES as $valeur => $libelle): ?>
                    <option value="<?= e($valeur) ?>" <?= $oeuvre['type'] === $valeur ? 'selected' : '' ?>><?= e($libelle) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="formulaire__ligne">
            <div class="formulaire__groupe">
                <label class="formulaire__label" for="statut_lecture">Statut de lecture</label>
                <select class="formulaire__champ" id="statut_lecture" name="statut_lecture">
                    <?php foreach (STATUTS_LECTURE as $valeur => $libelle): ?>
                        <option value="<?= e($valeur) ?>" <?= $oeuvre['statut_lecture'] === $valeur ? 'selected' : '' ?>><?= e($libelle) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="formulaire__groupe">
                <?php if ($oeuvre['physique']): ?>
                    <label class="formulaire__label" for="volume_actuel">Volume actuel</label>
                    <input class="formulaire__champ" type="number" min="0" id="volume_actuel" name="volume_actuel" value="<?= (int) $oeuvre['volume_actuel'] ?>">
                <?php else: ?>
                    <label class="formulaire__label" for="chapitre_actuel">Chapitre actuel</label>
                    <input class="formulaire__champ" type="number" min="0" id="chapitre_actuel" name="chapitre_actuel" value="<?= (int) $oeuvre['chapitre_actuel'] ?>">
                <?php endif; ?>
            </div>
        </div>

        <div class="formulaire__groupe">
            <label class="formulaire__label" for="lien">Lien de lecture</label>
            <input class="formulaire__champ" type="url" id="lien" name="lien" value="<?= e($oeuvre['lien']) ?>" placeholder="https://…">
        </div>

        <div class="formulaire__actions">
            <button class="bouton bouton--principal" type="submit">Enregistrer</button>
        </div>
    </form>

    <form class="panneau__danger" method="post" onsubmit="return confirm('Supprimer cette œuvre de ta bibliothèque ?');">
        <?= csrf_champ() ?>
        <input type="hidden" name="action" value="supprimer">
        <button class="bouton bouton--danger" type="submit">Supprimer de la bibliothèque</button>
    </form>
</section>

<?php require __DIR__ . '/templates/footer.php'; ?>
