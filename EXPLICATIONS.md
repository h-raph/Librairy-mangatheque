# Ce qui a été fait sur la MangaThèque

Un résumé de tout le travail réalisé depuis le début, étape par étape, sans entrer dans chaque ligne de code.

---

## 1. Le site de base (PHP + MySQL)

**Objectif :** enregistrer les mangas, manhwas et manhuas lus en ligne, les filtrer et les afficher.

### Les pages
| Fichier | Ce qu'il fait |
| --- | --- |
| `index.php` | La **bibliothèque** : toutes les œuvres en grille, avec les filtres à gauche |
| `ajouter.php` | L'**ajout** d'une lecture (recherche AniList ou saisie à la main) |
| `manga.php?id=…` | La **fiche** d'une œuvre : résumé, genres, progression, modification, suppression |
| `api/recherche.php` | Petit service appelé en JavaScript pendant que tu tapes dans la recherche |

### Les filtres
Recherche par titre, **genres** (plusieurs à la fois), type (manga / manhwa / manhua), statut de lecture, **favoris**, et **tri** : A → Z (par défaut), Z → A, plus récent, plus ancien.

### Comment le code est rangé
- `config/config.php` : les réglages (accès à la base, durée du cache).
- `src/` : la logique, séparée de l'affichage.
  - `db.php` : connexion à la base.
  - `anilist.php` : tout ce qui parle à l'API AniList.
  - `oeuvres.php` : lire, ajouter, modifier ou supprimer des œuvres en base.
  - `helpers.php` : petits outils (traductions des libellés, sécurité, messages).
- `templates/` : les morceaux de HTML communs (en-tête, pied de page, carte d'une œuvre).

### Sécurité, en bref
- Requêtes SQL **préparées** : pas d'injection SQL possible.
- Tout ce qui est affiché est **échappé** : pas de faille XSS.
- Les formulaires ont un **jeton CSRF** : un autre site ne peut pas envoyer de formulaire à ta place.

---

## 2. L'API AniList et le cache

- Quand tu cherches un titre, le site interroge **AniList** et récupère le titre, le **résumé**, la **cover**, les **genres** et le **statut de publication**.
- Le type est deviné tout seul selon le pays d'origine : Japon → manga, Corée → manhwa, Chine → manhua.
- **Pour ne pas appeler l'API à chaque fois :**
  - les réponses sont gardées dans la table `anilist_cache` (1 jour pour une recherche, 7 jours pour une fiche) ;
  - une fois l'œuvre ajoutée, ses infos sont enregistrées dans la table `oeuvres` et sa **cover est téléchargée** dans `assets/covers/`. L'affichage ne dépend plus du tout d'AniList.
- Sur une fiche, le bouton **« Actualiser »** va rechercher les infos à jour quand tu le veux.

---

## 3. La base de données

Le fichier `sql/schema.sql` crée la base `manga_tracker` avec 4 tables :

| Table | Contenu |
| --- | --- |
| `oeuvres` | Tes lectures (titre, type, chapitre, favori, lien, résumé, cover…) |
| `genres` | La liste des genres |
| `oeuvre_genre` | Le lien entre une œuvre et ses genres (une œuvre peut avoir plusieurs genres) |
| `anilist_cache` | Les réponses d'AniList mises de côté |

Ce script **a été exécuté** dans le MySQL de MAMP (port 8889). La base est prête.

---

## 4. Le passage au SCSS

Le CSS a été réécrit en **SCSS** dans `assets/scss/`, puis compilé avec Sass dans un seul fichier, `assets/css/main.css`. C'est le seul fichier que les pages chargent.

```bash
npm run build:css    # compile une fois
npm run watch:css    # recompile à chaque sauvegarde
```

### Organisation SMACSS
| Dossier | Rôle |
| --- | --- |
| `abstracts/` | Variables Sass et **mixins** (morceaux de style réutilisables) |
| `theme/` | Les couleurs, typos et arrondis du site |
| `base/` | Le style par défaut des balises HTML |
| `layout/` | Les grandes zones de la page (classes `.l-…`) |
| `module/` | Un fichier par composant : carte, bouton, filtres, fiche… |
| `state/` | Les états temporaires (classes `.is-active`, `.is-hidden`…) |

### Nommage BEM
Chaque composant suit le schéma `bloc__element--modificateur`, par exemple `.carte__titre` ou `.badge--manhwa`. En SCSS, ça s'écrit de façon imbriquée avec `&__` et `&--`.

Le JavaScript s'appuie sur des attributs `data-…` plutôt que sur les classes : on peut renommer une classe CSS sans casser le JS.

---

## 5. La nouvelle DA : Japon féodal

Thème **« washi & sumi »**, inspiré du papier japonais, de l'encre et des sceaux.

- **Couleurs :** fond papier washi (avec une légère texture), texte noir d'encre (*sumi*), **vermillon** des torii et des sceaux (*shu*), **indigo** (*ai*), vert **matcha** et une touche d'**or**.
- **Typographies** (Google Fonts) : *Shippori Mincho* pour le texte et les titres, *Yuji Syuku*, au style pinceau, pour les kanji décoratifs.
- **Logo :** un sceau rouge **hanko** avec le kanji 漫 (« manga »).
- **Cartes :** chaque œuvre ressemble à un **rouleau suspendu** (*kakejiku*), avec une baguette de bois en haut. Le type est apposé comme un sceau, un peu de travers.
- **Fiche :** la cover est montée comme un rouleau, avec une baguette en haut et en bas.
- **Kanji décoratifs :** 書庫 (bibliothèque), 記録 (enregistrer), 物語 (histoire), 読書 (lecture), 選 (choisir).
- **Bibliothèque vide :** un **ensō**, le cercle zen, autour du kanji 空 (« vide »).
- **Pied de page :** une frise de vagues **seigaiha** (青海波) au-dessus d'un bandeau noir laqué.
- **Formes :** angles presque droits, bordures fines et cadres doubles, comme du papier tendu.

---

## 6. Version téléphone

- **Filtres repliables :** sur un écran étroit, le panneau des filtres est replié et il suffit de toucher « Filtres » pour l'ouvrir. Un petit sceau rouge indique combien de filtres sont actifs. Le panneau reste ouvert d'une page à l'autre pendant la session, pour ne pas devoir le rouvrir à chaque filtre appliqué. Sur ordinateur, rien ne change.
- **Header :** sur téléphone, seul le sceau 漫 est affiché à la place du nom complet, pour que le menu tienne sans déborder.
- **Utiliser le site depuis le téléphone (même Wi-Fi) :** lancer le serveur avec `php -S 0.0.0.0:8000` (le chemin complet est plus bas), puis ouvrir `http://192.168.1.73:8000` sur le téléphone. L'IP du Mac peut changer.

---

## 7. Tri A → Z, langue retirée, favoris

- **Tri par défaut :** la bibliothèque est maintenant classée de A à Z.
- **Langue de lecture supprimée :** le champ a disparu des formulaires, des cartes, de la fiche et des filtres, et la colonne a été retirée de la base.
- **Favoris :** une étoile sur chaque carte et un bouton sous la cover de la fiche. Elle devient dorée quand l'œuvre est en favori. Le clic s'enregistre sans recharger la page. Un filtre « ★ Favoris uniquement » permet de n'afficher que tes favoris.
- **Base de données :** le changement a été fait avec le script `sql/migrations/001_favoris_sans_langue.sql`, déjà exécuté sur ta base MAMP. Tes œuvres ont été conservées. Ce script ne sert que pour une base créée avant ce changement : une nouvelle installation n'a besoin que de `schema.sql`.

---

## 8. Possession en physique et suivi par volume

- **Bouton « Je l'ai en physique » :** sur la fiche d'une œuvre, et sous forme de case à cocher dans le formulaire d'ajout.
- **Suivi par volume :** en physique, le volume remplace le chapitre sur la carte (`Vol. 3 / 15`), la fiche, le formulaire et le bouton « +1 volume lu ». Le total vient d'AniList quand il est connu.
- **Filtre :** « Physique uniquement » n'affiche que les œuvres que tu possèdes.
- **Rien n'est perdu :** chapitre et volume sont enregistrés séparément. Si tu décoches le physique, tu retrouves ton chapitre.
- **Base de données :** script `sql/migrations/002_physique_volumes.sql`, à lancer une fois sur chaque base (MAMP et alwaysdata). Tes œuvres sont conservées.

---

## Pour lancer le site

1. Démarrer **MAMP**.
2. Dans le dossier du projet :
   ```bash
   /Applications/MAMP/bin/php/php8.4.15/bin/php -S localhost:8000
   ```
3. Ouvrir http://localhost:8000
4. Après une modification du SCSS : `npm run build:css`, ou laisser tourner `npm run watch:css`.
