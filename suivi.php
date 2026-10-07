<?php
require 'config.php';
require 'helpers.php';

$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();
$id = (int) ($_GET['commande'] ?? 0);
$token = $_GET['token'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id AND qr_token = :token");
$stmt->execute(['id' => $id, 'token' => $token]);
$commande = $stmt->fetch();

$pageTitre = "Suivi de commande — " . $settings['brand_name'];
require 'partials/head.php';
?>

<div class="section-titre" style="margin-top:16px;">Suivi de commande</div>

<?php if (!$commande): ?>
    <div class="vide"><p>Commande introuvable — vérifie le lien ou le QR code scanné.</p></div>
<?php else:
    $items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :id");
    $items->execute(['id' => $id]);
    $lignes = $items->fetchAll();
    $etapes = array_keys(STATUTS_LIVRAISON);
    $etapeActuelle = array_search($commande['statut'], $etapes, true);
?>
    <div style="padding:0 18px;">
        <p><strong>Commande n°<?= (int) $commande['id'] ?></strong> — <?= h($commande['nom_client']) ?></p>

        <div style="display:flex; justify-content:space-between; margin:18px 0; position:relative;">
            <?php foreach ($etapes as $i => $e): ?>
                <div style="flex:1; text-align:center; position:relative;">
                    <div style="width:26px; height:26px; border-radius:50%; margin:0 auto 6px;
                        background:<?= $i <= $etapeActuelle ? 'var(--bleu)' : '#ddd6c4' ?>;
                        color:#fff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700;">
                        <?= $i <= $etapeActuelle ? '✓' : ($i + 1) ?>
                    </div>
                    <div style="font-size:11px; color:<?= $i <= $etapeActuelle ? 'var(--bleu)' : 'var(--gris)' ?>; font-weight:600;">
                        <?= h(STATUTS_LIVRAISON[$e]) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="note" style="padding:0;">Statut du paiement : <strong><?= h(STATUTS_PAIEMENT[$commande['statut_paiement']] ?? $commande['statut_paiement']) ?></strong></p>
    </div>

    <div class="section-titre">Contenu de la commande</div>
    <?php foreach ($lignes as $l): ?>
        <div class="ligne-panier">
            <div class="details">
                <h4><?= h($l['titre_outfit']) ?></h4>
                <div class="meta">Taille <?= h($l['taille']) ?> · x<?= (int) $l['quantite'] ?></div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php $navActif = ''; require 'partials/bottom-nav.php'; ?>
