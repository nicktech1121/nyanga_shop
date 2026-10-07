<?php
require 'config.php';
require 'helpers.php';

$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM outfits WHERE id = :id");
$stmt->execute(['id' => $id]);
$outfit = $stmt->fetch();

if (!$outfit) {
    header('Location: categorie.php');
    exit;
}

$imgs = $pdo->prepare("SELECT * FROM outfit_images WHERE outfit_id = :id ORDER BY ordre");
$imgs->execute(['id' => $id]);
$images = $imgs->fetchAll();

$stockStmt = $pdo->prepare("SELECT taille, stock FROM outfit_sizes WHERE outfit_id = :id AND stock > 0 ORDER BY taille");
$stockStmt->execute(['id' => $id]);
$taillesDisponibles = $stockStmt->fetchAll();
$tailles = array_column($taillesDisponibles, 'taille');
$enRupture = empty($tailles);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$outfit['vendu'] && !$enRupture) {
    $tailleChoisie = $_POST['taille'] ?? ($tailles[0] ?? 'M');
    panierAjouter($id, $tailleChoisie, 1);

    if (($_POST['action'] ?? '') === 'commander') {
        header('Location: checkout.php');
        exit;
    }
    header('Location: outfit.php?id=' . $id . '&ajoute=1');
    exit;
}

$ajoute = isset($_GET['ajoute']);
$pageTitre = $outfit['titre'] . ' — ' . $settings['brand_name'];
require 'partials/head.php';
?>

<div class="galerie">
    <div class="galerie-principale">
        <?php if (!empty($images)): ?>
            <img id="img-principale" src="uploads/<?= h($images[0]['image_path']) ?>" alt="<?= h($outfit['titre']) ?>">
        <?php else: ?>
            <div class="placeholder-img" style="height:100%;">Photo à venir</div>
        <?php endif; ?>
    </div>
    <?php if (count($images) > 1): ?>
        <div class="galerie-vignettes">
            <?php foreach ($images as $i => $img): ?>
                <img src="uploads/<?= h($img['image_path']) ?>" class="<?= $i === 0 ? 'actif' : '' ?>"
                     onclick="document.getElementById('img-principale').src=this.src; document.querySelectorAll('.galerie-vignettes img').forEach(e=>e.classList.remove('actif')); this.classList.add('actif');">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="fiche-corps">
    <div class="cat" style="color:var(--violet); font-weight:600; font-size:12px;"><?= h($outfit['categorie']) ?></div>
    <h1><?= h($outfit['titre']) ?></h1>
    <div class="prix-fiche"><?= (int) $outfit['prix'] ?> FCFA</div>

    <?php if ($outfit['vendu']): ?>
        <p class="erreur" style="margin:14px 0 0;">Cet outfit n'est plus disponible.</p>
    <?php elseif ($enRupture): ?>
        <p class="erreur" style="margin:14px 0 0;">Rupture de stock sur toutes les tailles.</p>
    <?php endif; ?>

    <?php if ($outfit['description']): ?>
        <div class="fiche-bloc">
            <h3>Les pièces de l'ensemble</h3>
            <p><?= nl2br(h($outfit['description'])) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($outfit['conseils']): ?>
        <div class="fiche-bloc">
            <h3>Comment le porter</h3>
            <p><?= nl2br(h($outfit['conseils'])) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!$outfit['vendu'] && !$enRupture): ?>
        <div class="fiche-bloc">
            <h3>Taille</h3>
            <form method="post" id="form-outfit">
                <select name="taille" class="select-taille" form="form-outfit">
                    <?php foreach ($taillesDisponibles as $td): ?>
                        <option value="<?= h($td['taille']) ?>"><?= h($td['taille']) ?> <?= $td['stock'] <= 2 ? '(dernières pièces)' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($ajoute): ?>
        <p class="note" style="color:var(--succes); font-weight:600; margin-top:12px;">Ajouté au panier ✓</p>
    <?php endif; ?>
</div>

<?php if (!$outfit['vendu'] && !$enRupture): ?>
<div class="barre-actions-fiche">
    <button type="submit" form="form-outfit" name="action" value="panier" class="btn-outline">Ajouter au panier</button>
    <button type="submit" form="form-outfit" name="action" value="commander" class="btn-plein">Commander maintenant</button>
</div>
<?php endif; ?>

<?php $navActif = 'categorie'; require 'partials/bottom-nav.php'; ?>
