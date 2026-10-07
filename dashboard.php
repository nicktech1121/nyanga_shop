<?php
require '../config.php';
require '../helpers.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle_vendu') {
        $pdo->prepare("UPDATE outfits SET vendu = NOT vendu WHERE id = :id")->execute(['id' => $id]);
    } elseif ($action === 'toggle_vedette') {
        $pdo->prepare("UPDATE outfits SET vedette = NOT vedette WHERE id = :id")->execute(['id' => $id]);
    } elseif ($action === 'supprimer') {
        $imgs = $pdo->prepare("SELECT image_path FROM outfit_images WHERE outfit_id = :id");
        $imgs->execute(['id' => $id]);
        foreach ($imgs->fetchAll(PDO::FETCH_COLUMN) as $img) {
            deleteImageFile($img, __DIR__ . '/../uploads');
        }
        $pdo->prepare("DELETE FROM outfits WHERE id = :id")->execute(['id' => $id]);
    }
    header('Location: dashboard.php');
    exit;
}

$outfits = $pdo->query("
    SELECT o.*, COALESCE((SELECT SUM(stock) FROM outfit_sizes s WHERE s.outfit_id = o.id), 0) AS stock_total
    FROM outfits o ORDER BY date_ajout DESC
")->fetchAll();
$ongletActif = 'dashboard';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Espace vendeur — Mes outfits</title>
<link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="admin-wrap">
    <div class="admin-header">
        <h2 style="font-size:22px; color:var(--bleu-fonce);">Espace vendeur</h2>
        <a href="logout.php" class="note" style="padding:0;">Déconnexion</a>
    </div>

    <?php require '_tabs.php'; ?>

    <a href="add.php" class="btn-principal" style="margin:0 0 16px; width:auto; display:inline-block;">+ Nouvel outfit</a>

    <?php if (empty($outfits)): ?>
        <p class="note" style="padding:0;">Pas encore d'outfit. Clique sur "Nouvel outfit" pour poster le premier.</p>
    <?php else: ?>
        <?php foreach ($outfits as $o): ?>
            <div class="ligne-article" style="margin:0 0 8px;">
                <div class="infos">
                    <strong><?= h($o['titre']) ?></strong>
                    <div>
                        <?= h($o['categorie']) ?> · <?= (int) $o['prix'] ?> FCFA
                        · Stock <?= (int) $o['stock_total'] ?><?= $o['stock_total'] == 0 ? ' ⚠️' : '' ?>
                        <?= $o['vendu'] ? '· Vendu' : '' ?>
                        <?= $o['vedette'] ? '· ⭐ Vedette' : '' ?>
                    </div>
                </div>
                <form method="post"><input type="hidden" name="id" value="<?= (int) $o['id'] ?>"><button class="icone-btn" name="action" value="toggle_vedette" title="Basculer vedette">⭐</button></form>
                <form method="post"><input type="hidden" name="id" value="<?= (int) $o['id'] ?>"><button class="icone-btn" name="action" value="toggle_vendu" title="Marquer vendu/disponible"><?= $o['vendu'] ? '↺' : '✓' ?></button></form>
                <a class="icone-btn" href="edit.php?id=<?= (int) $o['id'] ?>" title="Modifier">✎</a>
                <form method="post" onsubmit="return confirm('Supprimer cet outfit définitivement ?');"><input type="hidden" name="id" value="<?= (int) $o['id'] ?>"><button class="icone-btn" name="action" value="supprimer" style="color:var(--alerte);" title="Supprimer">✕</button></form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <p style="margin-top:20px;"><a href="../index.php" class="note" style="padding:0;">&larr; Voir le site public</a></p>
</div>
</body>
</html>
