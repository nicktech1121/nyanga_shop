<?php
require 'config.php';
require 'helpers.php';

$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();
if (!$settings) {
    die("Le site n'est pas encore installé. Ouvre install.php dans ton navigateur d'abord.");
}

$enStock = "EXISTS (SELECT 1 FROM outfit_sizes s WHERE s.outfit_id = o.id AND s.stock > 0)";

$vedettes = $pdo->query("
    SELECT o.*, (SELECT image_path FROM outfit_images WHERE outfit_id = o.id ORDER BY ordre LIMIT 1) AS image
    FROM outfits o WHERE vendu = FALSE AND vedette = TRUE AND $enStock
    ORDER BY date_ajout DESC LIMIT 6
")->fetchAll();

if (empty($vedettes)) {
    $vedettes = $pdo->query("
        SELECT o.*, (SELECT image_path FROM outfit_images WHERE outfit_id = o.id ORDER BY ordre LIMIT 1) AS image
        FROM outfits o WHERE vendu = FALSE AND $enStock
        ORDER BY date_ajout DESC LIMIT 6
    ")->fetchAll();
}

$pageTitre = $settings['brand_name'];
require 'partials/head.php';
?>

<div class="hero-accueil">
    <h1><?= h($settings['brand_name']) ?></h1>
    <p><?= h($settings['tagline']) ?></p>
</div>

<div class="section-titre">Nos meilleures ventes</div>

<?php if (empty($vedettes)): ?>
    <div class="vide"><p>Le catalogue arrive très bientôt. Reviens vite !</p></div>
<?php else: ?>
    <div class="grille">
        <?php foreach ($vedettes as $o): ?>
            <a class="carte-outfit" href="outfit.php?id=<?= (int) $o['id'] ?>">
                <span class="badge-vedette">Best-seller</span>
                <?php if ($o['image']): ?>
                    <img src="uploads/<?= h($o['image']) ?>" alt="<?= h($o['titre']) ?>">
                <?php else: ?>
                    <div class="placeholder-img">Photo à venir</div>
                <?php endif; ?>
                <div class="infos">
                    <div class="cat"><?= h($o['categorie']) ?></div>
                    <h4><?= h($o['titre']) ?></h4>
                    <div class="prix"><?= (int) $o['prix'] ?> FCFA</div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="section-titre">Parcourir par catégorie</div>
<div class="filtres" style="padding-bottom: 18px;">
    <?php foreach (CATEGORIES as $c): ?>
        <a class="pill" href="categorie.php?cat=<?= urlencode($c) ?>"><?= h($c) ?></a>
    <?php endforeach; ?>
</div>

<?php $navActif = 'categorie'; require 'partials/bottom-nav.php'; ?>
