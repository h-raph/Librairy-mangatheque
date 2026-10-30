// Applique les filtres dès qu'une liste déroulante ou une case change (Entrée ou « Appliquer » pour la recherche texte)

const formulaire = document.querySelector('[data-filtres]');
const bascule = formulaire.querySelector('[data-filtres-bascule]');
const mobile = window.matchMedia('(max-width: 860px)');
const CLE_OUVERT = 'filtres-ouverts';

formulaire.addEventListener('change', (evenement) => {
    if (evenement.target.matches('select, input[type="checkbox"]')) {
        formulaire.requestSubmit();
    }
});

// Sur mobile, le panneau est replié par défaut ; son état est retenu
// le temps de la session pour ne pas se refermer à chaque filtre appliqué
function lireEtat() {
    try {
        return sessionStorage.getItem(CLE_OUVERT) === '1';
    } catch {
        return false;
    }
}

function ecrireEtat(ouvert) {
    try {
        sessionStorage.setItem(CLE_OUVERT, ouvert ? '1' : '0');
    } catch {
        // stockage indisponible (navigation privée) : l'état n'est simplement pas retenu
    }
}

function appliquerEtat(ouvert) {
    const replie = mobile.matches && !ouvert;
    formulaire.classList.toggle('is-ferme', replie);
    bascule.setAttribute('aria-expanded', String(!replie));
}

let ouvert = lireEtat();
appliquerEtat(ouvert);

bascule.addEventListener('click', () => {
    if (!mobile.matches) {
        return;
    }
    ouvert = !ouvert;
    ecrireEtat(ouvert);
    appliquerEtat(ouvert);
});

mobile.addEventListener('change', () => appliquerEtat(ouvert));
