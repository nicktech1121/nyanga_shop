<?php
require 'config.php';
require 'helpers.php';

$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if (isset($_SESSION['panier'][$id])) {
        if ($action === 'plus') {
            $_SESSION['panier'][$id]['quantite']++;
        } elseif ($action === 'moins') {
            $_SESSION['panier'][$id]['quantite']--;
            if ($_SESSION['panier'][$id]['quantite'] <= 0) {
                unset($_SESSION['panier'][$id]);
            }
        } elseif ($action === 'supprimer') {
            unset($_SESSION['panier'][$id]);
        }
    }
    header('Location: panier.php');
    exit;
}

$lignes = [];
$total = 0;
if (!empty($_SESSION['panier'])) {
    $ids = array_keys($_SESSION['panier']);
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("
        SELECT o.*, (SELECT image_path FROM outfit_images WHERE outfit_id = o.id ORDER BY ordre LIMIT 1) AS image
        FROM outfits o WHERE id IN ($in)
    ");
    $stmt->execute($ids);
    $outfitsParId = [];
    foreach ($stmt->fetchAll() as $o) {
        $outfitsParId[$o['id']] = $o;
    }
    foreach ($_SESSION['panier'] as $oid => $info) {
        if (!isset($outfitsParId[$oid])) continue;
        $o = $outfitsParId[$oid];
        $sousTotal = $o['prix'] * $info['quantite'];
        $total += $sousTotal;
        $lignes[] = ['outfit' => $o, 'taille' => $info['taille'], 'quantite' => $info['quantite'], 'sousTotal' => $sousTotal];
    }
}

$pageTitre = "Panier — " . $settings['brand_name'];
require 'partials/head.php';
?>

<div class="section-titre" style="margin-top:16px;">Mon panier</div>

<?php if (empty($lignes)): ?>
    <div class="vide">
        <p>Ton panier est vide.</p>
        <p><a href="categorie.php" style="color:var(--bleu); font-weight:700;">Voir les outfits →</a></p>
    </div>
<?php else: ?>
    <?php foreach ($lignes as $l): $o = $l['outfit']; ?>
        <div class="ligne-panier">
            <?php if ($o['image']): ?>
                <img src="uploads/<?= h($o['image']) ?>" alt="">
            <?php else: ?>
                <div class="placeholder-img"></div>
            <?php endif; ?>
            <div class="details">
                <h4><?= h($o['titre']) ?></h4>
                <div class="meta">Taille <?= h($l['taille']) ?> · <?= (int) $o['prix'] ?> FCFA</div>
                <div class="qte-controls">
                    <form method="post"><input type="hidden" name="id" value="<?= (int) $o['id'] ?>"><button class="qte-btn" name="action" value="moins">−</button></form>
                    <span><?= $l['quantite'] ?></span>
                    <form method="post"><input type="hidden" name="id" value="<?= (int) $o['id'] ?>"><button class="qte-btn" name="action" value="plus">+</button></form>
                    <form method="post" style="margin-left:auto;"><input type="hidden" name="id" value="<?= (int) $o['id'] ?>"><button class="qte-btn" name="action" value="supprimer" style="border-color:var(--alerte); color:var(--alerte);">✕</button></form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="panier-total"><span>Total</span><span><?= $total ?> FCFA</span></div>

    <a href="checkout.php" class="btn-principal" style="display:block; text-align:center;">Passer la commande</a>
<?php endif; ?>

<?php $navActif = 'panier'; require 'partials/bottom-nav.php'; ?>
