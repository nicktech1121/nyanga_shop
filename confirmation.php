<?php
require 'config.php';
require 'helpers.php';

$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
$stmt->execute(['id' => $id]);
$commande = $stmt->fetch();

if (!$commande) {
    header('Location: index.php');
    exit;
}

$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :id");
$items->execute(['id' => $id]);
$lignes = $items->fetchAll();
$total = array_sum(array_map(fn($l) => $l['prix_unitaire'] * $l['quantite'], $lignes));

$lienDeSuivi = lienSuivi($settings['base_url'], $commande['id'], $commande['qr_token']);

$pageTitre = "Commande confirmée";
require 'partials/head.php';
?>

<div class="stub">
    <div class="icone">✅</div>
    <h2 style="color:var(--texte); font-size:18px;">Commande n°<?= (int) $commande['id'] ?> confirmée</h2>
    <p>Merci <?= h($commande['nom_client']) ?> ! On t'appelle bientôt au <?= h($commande['telephone']) ?> pour organiser la livraison à <?= h($commande['quartier']) ?>.</p>

    <?php if ($commande['statut_paiement'] === 'en_verification'): ?>
        <p class="note" style="color:#8A6A2F; font-weight:600;">Paiement Mobile Money reçu — en attente de vérification par le vendeur.</p>
    <?php endif; ?>
</div>

<div class="section-titre">Ton QR code de suivi</div>
<div style="text-align:center; padding:0 18px 10px;">
    <img src="<?= h(urlQrCode($lienDeSuivi)) ?>" alt="QR code de suivi" style="border-radius:8px; border:1px solid #eee;">
    <p class="note" style="padding:8px 0 0;">Il sera collé sur ton colis — scanne-le à la livraison pour vérifier que c'est bien ta commande, ou <a href="suivi.php?commande=<?= (int) $commande['id'] ?>&token=<?= h($commande['qr_token']) ?>" style="color:var(--bleu); font-weight:600;">clique ici pour suivre ta commande</a>.</p>
</div>

<div class="section-titre">Récapitulatif</div>
<?php foreach ($lignes as $l): ?>
    <div class="ligne-panier">
        <div class="details">
            <h4><?= h($l['titre_outfit']) ?></h4>
            <div class="meta">Taille <?= h($l['taille']) ?> · x<?= (int) $l['quantite'] ?> · <?= (int) $l['prix_unitaire'] ?> FCFA</div>
        </div>
    </div>
<?php endforeach; ?>
<div class="panier-total"><span>Total</span><span><?= $total ?> FCFA</span></div>

<?php $navActif = ''; require 'partials/bottom-nav.php'; ?>
