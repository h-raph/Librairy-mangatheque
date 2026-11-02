// Recherche AniList (via api/recherche.php, qui met les réponses en cache) et pré-remplissage du formulaire

const bloc = document.querySelector('[data-recherche]');
const champ = bloc.querySelector('[data-recherche-champ]');
const liste = bloc.querySelector('[data-recherche-resultats]');
const message = bloc.querySelector('[data-recherche-message]');

const champAnilist = document.querySelector('[data-champ-anilist]');
const champTitre = document.querySelector('[data-champ-titre]');
const champType = document.querySelector('[data-champ-type]');

const apercu = document.querySelector('[data-apercu]');
const apercuImage = apercu.querySelector('[data-apercu-image]');
const apercuTitre = apercu.querySelector('[data-apercu-titre]');
const apercuMeta = apercu.querySelector('[data-apercu-meta]');
const apercuResume = apercu.querySelector('[data-apercu-resume]');

let minuteur = null;
let controleur = null;

function afficherMessage(texte, estErreur = false) {
    message.textContent = texte;
    message.classList.toggle('is-hidden', !texte);
    message.classList.toggle('is-error', estErreur);
}

function creer(balise, classe, texte = '') {
    const element = document.createElement(balise);
    element.className = classe;
    element.textContent = texte;
    return element;
}

function afficherResultats(resultats) {
    liste.replaceChildren();

    if (!resultats.length) {
        afficherMessage('Aucun résultat sur AniList.');
        return;
    }
    afficherMessage('');

    for (const oeuvre of resultats) {
        const item = creer('li', 'recherche__item');
        const bouton = creer(oeuvre.oeuvre_id ? 'a' : 'button', 'recherche__bouton');

        if (oeuvre.oeuvre_id) {
            item.classList.add('recherche__item--deja');
            bouton.href = `manga.php?id=${oeuvre.oeuvre_id}`;
        } else {
            bouton.type = 'button';
            bouton.addEventListener('click', () => selectionner(oeuvre));
        }

        const image = creer('img', 'recherche__image');
        image.src = oeuvre.cover_url ?? 'assets/img/placeholder.svg';
        image.alt = '';
        image.loading = 'lazy';

        const infos = creer('span', 'recherche__infos');
        infos.append(
            creer('span', 'recherche__titre', oeuvre.titre),
            creer('span', 'recherche__meta', [oeuvre.type_libelle, oeuvre.annee, oeuvre.statut_libelle].filter(Boolean).join(' · ')),
        );
        if (oeuvre.oeuvre_id) {
            infos.append(creer('span', 'recherche__deja', 'Déjà dans ta bibliothèque'));
        }

        bouton.append(image, infos);
        item.append(bouton);
        liste.append(item);
    }
}

async function rechercher(terme) {
    controleur?.abort();
    controleur = new AbortController();
    bloc.classList.add('is-loading');

    try {
        const reponse = await fetch(`api/recherche.php?q=${encodeURIComponent(terme)}`, { signal: controleur.signal });
        const donnees = await reponse.json();

        if (!reponse.ok) {
            throw new Error(donnees.erreur ?? 'Erreur lors de la recherche.');
        }
        afficherResultats(donnees.resultats);
    } catch (erreur) {
        if (erreur.name !== 'AbortError') {
            liste.replaceChildren();
            afficherMessage(erreur.message, true);
        }
    } finally {
        bloc.classList.remove('is-loading');
    }
}

function selectionner(oeuvre) {
    champAnilist.value = oeuvre.anilist_id;
    champTitre.value = oeuvre.titre;
    champType.value = oeuvre.type;

    apercuImage.src = oeuvre.cover_url ?? 'assets/img/placeholder.svg';
    apercuTitre.textContent = oeuvre.titre;
    apercuMeta.textContent = [oeuvre.type_libelle, oeuvre.statut_libelle, ...oeuvre.genres_fr].filter(Boolean).join(' · ');
    apercuResume.textContent = oeuvre.resume ?? '';
    apercu.classList.remove('is-hidden');

    liste.replaceChildren();
    champ.value = '';
}

function retirer() {
    champAnilist.value = '';
    apercu.classList.add('is-hidden');
    champ.focus();
}

champ.addEventListener('input', () => {
    clearTimeout(minuteur);
    const terme = champ.value.trim();

    if (terme.length < 2) {
        liste.replaceChildren();
        afficherMessage('');
        return;
    }
    minuteur = setTimeout(() => rechercher(terme), 350);
});

apercu.querySelector('[data-apercu-retirer]').addEventListener('click', retirer);

// En physique, on suit le volume plutôt que le chapitre
const champPhysique = document.querySelector('[data-champ-physique]');

champPhysique.addEventListener('change', () => {
    document.querySelector('[data-progression-chapitre]').classList.toggle('is-hidden', champPhysique.checked);
    document.querySelector('[data-progression-volume]').classList.toggle('is-hidden', !champPhysique.checked);
});
