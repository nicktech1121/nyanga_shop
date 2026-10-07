<?php
require '../config.php';
require '../helpers.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if (($_POST['champ'] ?? '') === 'statut') {
        $pdo->prepare("UPDATE orders SET statut = :s WHERE id = :id")->execute(['s' => $_POST['valeur'], 'id' => $id]);
    } elseif (($_POST['champ'] ?? '') === 'statut_paiement') {
        $pdo->prepare("UPDATE orders SET statut_paiement = :s WHERE id = :id")->execute(['s' => $_POST['valeur'], 'id' => $id]);
    }
    header('Location: commandes.php');
    exit;
}

$commandes = $pdo->query("SELECT * FROM orders ORDER BY date_commande DESC")->fetchAll();
$itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :id");

$labelPaiementMode = [
    'paiement_livraison' => 'À la livraison',
    'mobile_money_mtn' => 'MTN Mobile Money',
    'mobile_money_orange' => 'Orange Money',
];

$ongletActif = 'commandes';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Commandes</title>
<link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="admin-wrap">
    <div class="admin-header">
        <h2 style="font-size:22px; color:var(--bleu-fonce);">Espace vendeur</h2>
        <a href="logout.php" class="note" style="padding:0;">Déconnexion</a>
    </div>

    <?php require '_tabs.php'; ?>

    <?php if (empty($commandes)): ?>
        <p class="note" style="padding:0;">Aucune commande pour le moment.</p>
    <?php else: ?>
        <?php foreach ($commandes as $cmd): $itemsStmt->execute(['id' => $cmd['id']]); $lignes = $itemsStmt->fetchAll(); ?>
            <div class="ligne-article" style="flex-direction:column; align-items:stretch; margin:0 0 14px;">
                <div style="display:flex; justify-content:space-between;">
                    <strong>Commande #<?= (int) $cmd['id'] ?> — <?= h($cmd['nom_client']) ?></strong>
                    <span class="note" style="padding:0;"><?= h(date('d/m/Y H:i', strtotime($cmd['date_commande']))) ?></span>
                </div>
                <div class="note" style="padding:4px 0;"><?= h($cmd['quartier']) ?> · <?= h($cmd['telephone']) ?></div>
                <?php foreach ($lignes as $l): ?>
                    <div style="font-size:13px; padding:2px 0;">• <?= h($l['titre_outfit']) ?> (taille <?= h($l['taille']) ?>) x<?= (int) $l['quantite'] ?> — <?= (int) $l['prix_unitaire'] ?> FCFA</div>
                <?php endforeach; ?>

                <div class="note" style="padding:6px 0 0;">
                    Paiement : <strong><?= h($labelPaiementMode[$cmd['mode_paiement']] ?? $cmd['mode_paiement']) ?></strong>
                    <?php if ($cmd['transaction_id']): ?> — ID transaction : <code><?= h($cmd['transaction_id']) ?></code><?php endif; ?>
                </div>

                <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:8px;">
                    <form method="post" style="display:flex; gap:6px; align-items:center;">
                        <input type="hidden" name="id" value="<?= (int) $cmd['id'] ?>">
                        <input type="hidden" name="champ" value="statut_paiement">
                        <select name="valeur" style="padding:6px; border-radius:6px; border:1px solid #ddd6c4; font-size:12px;">
                            <?php foreach (STATUTS_PAIEMENT as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $cmd['statut_paiement'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="icone-btn" style="border:1px solid var(--bleu); color:var(--bleu); border-radius:6px; font-size:11px;">OK</button>
                    </form>

                    <form method="post" style="display:flex; gap:6px; align-items:center;">
                        <input type="hidden" name="id" value="<?= (int) $cmd['id'] ?>">
                        <input type="hidden" name="champ" value="statut">
                        <select name="valeur" style="padding:6px; border-radius:6px; border:1px solid #ddd6c4; font-size:12px;">
                            <?php foreach (STATUTS_LIVRAISON as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $cmd['statut'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="icone-btn" style="border:1px solid var(--bleu); color:var(--bleu); border-radius:6px; font-size:11px;">OK</button>
                    </form>

                    <a href="print-label.php?id=<?= (int) $cmd['id'] ?>" target="_blank" class="icone-btn" style="border:1px solid var(--violet); color:var(--violet); border-radius:6px; font-size:12px;">🖨 Étiquette QR</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
