<?php
require '../config.php';
require '../helpers.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $outfitId = (int) ($_POST['outfit_id'] ?? 0);
    foreach (TAILLES as $t) {
        if (isset($_POST['stock'][$t])) {
            $val = max(0, (int) $_POST['stock'][$t]);
            $pdo->prepare(
                "INSERT INTO outfit_sizes (outfit_id, taille, stock) VALUES (:oid, :t, :s)
                 ON CONFLICT (outfit_id, taille) DO UPDATE SET stock = :s"
            )->execute(['oid' => $outfitId, 't' => $t, 's' => $val]);
        }
    }
    header('Location: stock.php');
    exit;
}

$outfits = $pdo->query("SELECT id, titre, categorie, vendu FROM outfits ORDER BY titre")->fetchAll();
$stockStmt = $pdo->prepare("SELECT taille, stock FROM outfit_sizes WHERE outfit_id = :id ORDER BY taille");

$ongletActif = 'stock';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Gestion des stocks</title>
<link rel="stylesheet" href="../style.css">
<style>
.stock-row { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
.stock-row label { font-size: 12px; font-weight: 600; display: flex; flex-direction: column; gap: 2px; }
.stock-row input { width: 54px; padding: 6px; border-radius: 6px; border: 1px solid #ddd6c4; }
.stock-row input.rupture { border-color: var(--alerte); background: #FBEAE8; }
.stock-row input.bas { border-color: #C98A2E; background: #FBF1E3; }
</style>
</head>
<body>
<div class="admin-wrap">
    <div class="admin-header">
        <h2 style="font-size:22px; color:var(--bleu-fonce);">Espace vendeur</h2>
        <a href="logout.php" class="note" style="padding:0;">Déconnexion</a>
    </div>

    <?php require '_tabs.php'; ?>

    <p class="note" style="padding:0; margin-bottom:14px;">
        Modifie les quantités puis valide. En rouge : rupture (0). En orange : stock bas (≤ 2).
    </p>

    <?php if (empty($outfits)): ?>
        <p class="note" style="padding:0;">Aucun outfit pour le moment.</p>
    <?php endif; ?>

    <?php foreach ($outfits as $o): $stockStmt->execute(['id' => $o['id']]); $stocks = array_fill_keys(TAILLES, 0);
        foreach ($stockStmt->fetchAll() as $row) { $stocks[$row['taille']] = (int) $row['stock']; }
        $total = array_sum($stocks);
    ?>
        <div class="ligne-article" style="flex-direction:column; align-items:stretch; margin:0 0 12px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                <strong><?= h($o['titre']) ?></strong>
                <span class="note" style="padding:0;"><?= h($o['categorie']) ?> · total <?= $total ?><?= $total === 0 ? ' ⚠️ rupture' : '' ?></span>
            </div>
            <form method="post">
                <input type="hidden" name="outfit_id" value="<?= (int) $o['id'] ?>">
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <?php foreach (TAILLES as $t): $classe = $stocks[$t] === 0 ? 'rupture' : ($stocks[$t] <= 2 ? 'bas' : ''); ?>
                        <div class="stock-row">
                            <label><?= h($t) ?><input type="number" name="stock[<?= h($t) ?>]" min="0" value="<?= $stocks[$t] ?>" class="<?= $classe ?>"></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="icone-btn" style="border:1px solid var(--bleu); color:var(--bleu); border-radius:6px; margin-top:6px;">Mettre à jour le stock</button>
            </form>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
