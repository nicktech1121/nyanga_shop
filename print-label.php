<?php
require '../config.php';
require '../helpers.php';
requireLogin();

$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
$stmt->execute(['id' => $id]);
$commande = $stmt->fetch();

if (!$commande) {
    die("Commande introuvable.");
}

$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :id");
$items->execute(['id' => $id]);
$lignes = $items->fetchAll();

$lien = lienSuivi($settings['base_url'], $commande['id'], $commande['qr_token']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Étiquette — Commande #<?= (int) $commande['id'] ?></title>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; max-width: 360px; margin: 0 auto; }
    .etiquette { border: 2px dashed #333; padding: 16px; text-align: center; }
    h2 { margin: 4px 0; font-size: 18px; }
    .qr { margin: 12px 0; }
    .infos { text-align: left; font-size: 13px; margin-top: 10px; }
    .infos div { margin-bottom: 4px; }
    button { margin-top: 16px; padding: 10px 16px; font-weight: bold; cursor: pointer; }
    @media print { button { display: none; } }
</style>
</head>
<body>
<div class="etiquette">
    <h2>Nyanga Shop</h2>
    <div>Commande #<?= (int) $commande['id'] ?></div>
    <div class="qr">
        <img src="<?= h(urlQrCode($lien, 180)) ?>" alt="QR code">
    </div>
    <div class="infos">
        <div><strong>Client :</strong> <?= h($commande['nom_client']) ?></div>
        <div><strong>Quartier :</strong> <?= h($commande['quartier']) ?></div>
        <div><strong>Téléphone :</strong> <?= h($commande['telephone']) ?></div>
        <div><strong>Contenu :</strong>
            <?php foreach ($lignes as $l): ?>
                <?= h($l['titre_outfit']) ?> (taille <?= h($l['taille']) ?>, x<?= (int) $l['quantite'] ?>)<br>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<button onclick="window.print()">Imprimer</button>
</body>
</html>
