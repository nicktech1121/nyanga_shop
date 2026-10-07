<?php
require '../config.php';
require '../helpers.php';
requireLogin();

$caTotal = (int) $pdo->query("SELECT COALESCE(SUM(prix_unitaire * quantite), 0) FROM order_items")->fetchColumn();
$caPaye = (int) $pdo->query("
    SELECT COALESCE(SUM(oi.prix_unitaire * oi.quantite), 0)
    FROM order_items oi JOIN orders o ON o.id = oi.order_id
    WHERE o.statut_paiement = 'paye'
")->fetchColumn();
$nbCommandes = (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$panierMoyen = $nbCommandes > 0 ? round($caTotal / $nbCommandes) : 0;

$topOutfits = $pdo->query("
    SELECT titre_outfit, SUM(quantite) AS total_vendu, SUM(prix_unitaire * quantite) AS ca
    FROM order_items GROUP BY titre_outfit ORDER BY total_vendu DESC LIMIT 5
")->fetchAll();

$parStatutLivraison = $pdo->query("
    SELECT statut, COUNT(*) AS nb FROM orders GROUP BY statut
")->fetchAll(PDO::FETCH_KEY_PAIR);

$parStatutPaiement = $pdo->query("
    SELECT statut_paiement, COUNT(*) AS nb FROM orders GROUP BY statut_paiement
")->fetchAll(PDO::FETCH_KEY_PAIR);

$parCategorie = $pdo->query("
    SELECT o.categorie, COALESCE(SUM(oi.quantite), 0) AS total_vendu
    FROM outfits o LEFT JOIN order_items oi ON oi.outfit_id = o.id
    GROUP BY o.categorie ORDER BY total_vendu DESC
")->fetchAll();

$ruptures = (int) $pdo->query("
    SELECT COUNT(DISTINCT o.id) FROM outfits o
    WHERE o.vendu = FALSE AND NOT EXISTS (SELECT 1 FROM outfit_sizes s WHERE s.outfit_id = o.id AND s.stock > 0)
")->fetchColumn();

$ongletActif = 'statistiques';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Statistiques</title>
<link rel="stylesheet" href="../style.css">
<style>
.stat-cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 20px; }
.stat-card { background: #faf8f2; border: 1px solid #eee; border-radius: 8px; padding: 12px; }
.stat-card .valeur { font-size: 20px; font-weight: 700; color: var(--bleu-fonce); }
.stat-card .label { font-size: 11px; color: var(--gris); margin-top: 2px; }
.stat-section h3 { font-size: 14px; margin: 18px 0 8px; }
.stat-bar-row { display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0; border-bottom: 1px solid #f0ede4; }
</style>
</head>
<body>
<div class="admin-wrap">
    <div class="admin-header">
        <h2 style="font-size:22px; color:var(--bleu-fonce);">Espace vendeur</h2>
        <a href="logout.php" class="note" style="padding:0;">Déconnexion</a>
    </div>

    <?php require '_tabs.php'; ?>

    <div class="stat-cards">
        <div class="stat-card"><div class="valeur"><?= $caTotal ?> FCFA</div><div class="label">Chiffre d'affaires total (commandé)</div></div>
        <div class="stat-card"><div class="valeur"><?= $caPaye ?> FCFA</div><div class="label">Dont déjà payé (vérifié)</div></div>
        <div class="stat-card"><div class="valeur"><?= $nbCommandes ?></div><div class="label">Commandes</div></div>
        <div class="stat-card"><div class="valeur"><?= $panierMoyen ?> FCFA</div><div class="label">Panier moyen</div></div>
    </div>

    <?php if ($ruptures > 0): ?>
        <p class="note" style="padding:0; color:var(--alerte); font-weight:600;">⚠️ <?= $ruptures ?> outfit(s) en rupture totale de stock — <a href="stock.php" style="color:var(--alerte);">voir le stock</a></p>
    <?php endif; ?>

    <div class="stat-section">
        <h3>Top 5 des outfits les plus vendus</h3>
        <?php if (empty($topOutfits)): ?>
            <p class="note" style="padding:0;">Pas encore de ventes.</p>
        <?php else: foreach ($topOutfits as $t): ?>
            <div class="stat-bar-row"><span><?= h($t['titre_outfit']) ?></span><span><?= (int) $t['total_vendu'] ?> vendus · <?= (int) $t['ca'] ?> FCFA</span></div>
        <?php endforeach; endif; ?>
    </div>

    <div class="stat-section">
        <h3>Ventes par catégorie</h3>
        <?php foreach ($parCategorie as $c): ?>
            <div class="stat-bar-row"><span><?= h($c['categorie']) ?></span><span><?= (int) $c['total_vendu'] ?> vendus</span></div>
        <?php endforeach; ?>
    </div>

    <div class="stat-section">
        <h3>Commandes par statut de livraison</h3>
        <?php foreach (STATUTS_LIVRAISON as $key => $label): ?>
            <div class="stat-bar-row"><span><?= $label ?></span><span><?= (int) ($parStatutLivraison[$key] ?? 0) ?></span></div>
        <?php endforeach; ?>
    </div>

    <div class="stat-section">
        <h3>Commandes par statut de paiement</h3>
        <?php foreach (STATUTS_PAIEMENT as $key => $label): ?>
            <div class="stat-bar-row"><span><?= $label ?></span><span><?= (int) ($parStatutPaiement[$key] ?? 0) ?></span></div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
