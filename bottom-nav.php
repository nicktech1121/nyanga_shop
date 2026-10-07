<?php
$nbPanier = panierCompte();
$bp = $basePath ?? '';
?>
<div class="bottom-nav">
    <a class="nav-item <?= ($navActif ?? '') === 'categorie' ? 'actif' : '' ?>" href="<?= $bp ?>categorie.php">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
        Catégorie
    </a>
    <a class="nav-item <?= ($navActif ?? '') === 'messagerie' ? 'actif' : '' ?>" href="<?= $bp ?>messagerie.php">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
        Messagerie
    </a>
    <a class="nav-item <?= ($navActif ?? '') === 'panier' ? 'actif' : '' ?>" href="<?= $bp ?>panier.php" style="position:relative;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        Panier
        <?php if ($nbPanier > 0): ?><span class="nav-badge"><?= $nbPanier ?></span><?php endif; ?>
    </a>
</div>

</body>
</html>
