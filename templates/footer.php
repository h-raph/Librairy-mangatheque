<?php
/** @var string[] $scripts */
?>
    </main>

    <footer class="l-footer footer">
        <div class="l-container footer__inner">
            <p class="footer__texte">Résumés, covers et genres fournis par <a class="footer__lien" href="https://anilist.co" target="_blank" rel="noopener">AniList</a>.</p>
        </div>
    </footer>

    <?php foreach ($scripts ?? [] as $script): ?>
        <script src="<?= e(asset($script)) ?>" defer></script>
    <?php endforeach; ?>
</body>
</html>
