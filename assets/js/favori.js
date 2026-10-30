// Bascule favori sans recharger la page (le formulaire fonctionne aussi sans JS)

document.addEventListener('submit', async (evenement) => {
    const formulaire = evenement.target.closest('[data-favori]');
    if (!formulaire) {
        return;
    }
    evenement.preventDefault();

    const bouton = formulaire.querySelector('.favori__bouton');
    bouton.disabled = true;

    try {
        const reponse = await fetch(formulaire.action, {
            method: 'POST',
            body: new FormData(formulaire),
            headers: { Accept: 'application/json' },
        });
        if (!reponse.ok) {
            throw new Error();
        }

        const { favori } = await reponse.json();
        const libelle = favori ? 'Retirer des favoris' : 'Ajouter aux favoris';

        bouton.classList.toggle('is-active', favori);
        bouton.setAttribute('aria-pressed', String(favori));
        bouton.setAttribute('aria-label', libelle);
        bouton.title = libelle;
        formulaire.querySelector('[data-favori-texte]').textContent = favori ? 'Favori' : 'Ajouter aux favoris';
    } catch {
        formulaire.submit();
    } finally {
        bouton.disabled = false;
    }
});
