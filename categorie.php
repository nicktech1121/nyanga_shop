<?php
require 'config.php';
require 'helpers.php';

$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();
$catFiltre = $_GET['cat'] ?? 'Tous';

$sql = "SELECT o.*, (SELECT image_path FROM outfit_images WHERE outfit_id = o.id ORDER BY ordre LIMIT 1) AS image
        FROM outfits o WHERE vendu = FALSE
        AND EXISTS (SELECT 1 FROM outfit_sizes s WHERE s.outfit_id = o.id AND s.stock > 0)";
$params = [];
if ($catFiltre !== 'Tous' && in_array($catFiltre, CATEGORIES, true)) {
    $sql .= " AND categorie = :cat";
    $params['cat'] = $catFiltre;
}
$sql .= " ORDER BY date_ajout DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$outfits = $stmt->fetchAll();

$pageTitre = "Catégorie — " . $settings['brand_name'];
require 'partials/head.php';
?>

<div class="section-titre" style="margin-top:16px;">Nos outfits complets</div>

<div class="filtres">
    <a href="categorie.php" class="pill <?= $catFiltre === 'Tous' ? 'actif' : '' ?>">Tous</a>
    <?php foreach (CATEGORIES as $c): ?>
        <a href="categorie.php?cat=<?= urlencode($c) ?>" class="pill <?= $catFiltre === $c ? 'actif' : '' ?>"><?= h($c) ?></a>
    <?php endforeach; ?>
</div>

<?php if (empty($outfits)): ?>
    <div class="vide"><p>Aucun outfit dans cette catégorie pour le moment.</p></div>
<?php else: ?>
    <div class="grille">
        <?php foreach ($outfits as $o): ?>
            <a class="carte-outfit" href="outfit.php?id=<?= (int) $o['id'] ?>">
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

<?php $navActif = 'categorie'; require 'partials/bottom-nav.php'; ?>
