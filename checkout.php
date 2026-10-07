<?php
require 'config.php';
require 'helpers.php';

$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();

if (empty($_SESSION['panier'])) {
    header('Location: panier.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $quartier = trim($_POST['quartier'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $modePaiement = $_POST['mode_paiement'] ?? 'paiement_livraison';
    $transactionId = trim($_POST['transaction_id'] ?? '');

    $modesValides = ['paiement_livraison', 'mobile_money_mtn', 'mobile_money_orange'];

    $outfitsParId = [];

    if ($nom === '' || $quartier === '' || $telephone === '') {
        $erreur = "Tous les champs sont obligatoires pour organiser la livraison.";
    } elseif (!in_array($modePaiement, $modesValides, true)) {
        $erreur = "Mode de paiement invalide.";
    } elseif ($modePaiement !== 'paiement_livraison' && $transactionId === '') {
        $erreur = "Indique l'ID de transaction reçu par SMS après ton paiement Mobile Money.";
    } else {
        $ids = array_keys($_SESSION['panier']);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM outfits WHERE id IN ($in)");
        $stmt->execute($ids);
        foreach ($stmt->fetchAll() as $o) {
            $outfitsParId[$o['id']] = $o;
        }

        // Vérifie le stock réel avant de valider la commande.
        $stockStmt = $pdo->prepare("SELECT stock FROM outfit_sizes WHERE outfit_id = :oid AND taille = :t");
        foreach ($_SESSION['panier'] as $oid => $info) {
            if (!isset($outfitsParId[$oid])) continue;
            $stockStmt->execute(['oid' => $oid, 't' => $info['taille']]);
            $stockDispo = (int) $stockStmt->fetchColumn();
            if ($stockDispo < $info['quantite']) {
                $erreur = "Stock insuffisant pour \"" . $outfitsParId[$oid]['titre'] . "\" en taille " . $info['taille'] . " (il reste $stockDispo en stock).";
                break;
            }
        }
    }

    if (!$erreur) {
        $statutPaiement = $modePaiement === 'paiement_livraison' ? 'non_paye' : 'en_verification';
        $qrToken = genererQrToken();

        $pdo->beginTransaction();
        $insOrder = $pdo->prepare(
            "INSERT INTO orders (nom_client, quartier, telephone, mode_paiement, transaction_id, statut_paiement, qr_token, customer_id)
             VALUES (:nom, :quartier, :tel, :mode, :tx, :sp, :qr, :cid) RETURNING id"
        );
        $insOrder->execute([
            'nom' => $nom, 'quartier' => $quartier, 'tel' => $telephone,
            'mode' => $modePaiement, 'tx' => $transactionId ?: null,
            'sp' => $statutPaiement, 'qr' => $qrToken,
            'cid' => $_SESSION['client_id'] ?? null,
        ]);
        $orderId = $insOrder->fetchColumn();

        $insItem = $pdo->prepare(
            "INSERT INTO order_items (order_id, outfit_id, titre_outfit, taille, prix_unitaire, quantite)
             VALUES (:oid, :outfit_id, :titre, :taille, :prix, :qte)"
        );
        $decrStock = $pdo->prepare(
            "UPDATE outfit_sizes SET stock = stock - :qte WHERE outfit_id = :oid AND taille = :t"
        );
        foreach ($_SESSION['panier'] as $oid => $info) {
            if (!isset($outfitsParId[$oid])) continue;
            $o = $outfitsParId[$oid];
            $insItem->execute([
                'oid' => $orderId,
                'outfit_id' => $oid,
                'titre' => $o['titre'],
                'taille' => $info['taille'],
                'prix' => $o['prix'],
                'qte' => $info['quantite'],
            ]);
            $decrStock->execute(['qte' => $info['quantite'], 'oid' => $oid, 't' => $info['taille']]);
        }
        $pdo->commit();

        unset($_SESSION['panier']);
        header('Location: confirmation.php?id=' . $orderId);
        exit;
    }
}

$pageTitre = "Commande — " . $settings['brand_name'];
require 'partials/head.php';
?>

<div class="section-titre" style="margin-top:16px;">Finaliser la commande</div>
<p class="note" style="margin-bottom:14px;">On te contactera au numéro indiqué pour organiser la livraison.</p>

<form method="post" id="form-checkout">
    <?php
    $clientInfo = null;
    if (clientConnecte()) {
        $ci = $pdo->prepare("SELECT nom, telephone FROM customers WHERE id = :id");
        $ci->execute(['id' => $_SESSION['client_id']]);
        $clientInfo = $ci->fetch();
    }
    ?>
    <label class="champ">
        Nom complet
        <input type="text" name="nom" required value="<?= h($_POST['nom'] ?? $clientInfo['nom'] ?? '') ?>">
    </label>
    <label class="champ">
        Quartier
        <input type="text" name="quartier" required value="<?= h($_POST['quartier'] ?? '') ?>" placeholder="Ex : Bonamoussadi, Douala">
    </label>
    <label class="champ">
        Numéro de téléphone à appeler pour la livraison
        <input type="text" name="telephone" required value="<?= h($_POST['telephone'] ?? $clientInfo['telephone'] ?? '') ?>" placeholder="6XX XXX XXX">
    </label>

    <label class="champ">Mode de paiement</label>
    <div class="checkbox-group" style="flex-direction:column; align-items:flex-start; gap:10px;">
        <label style="display:flex; gap:6px; align-items:center;">
            <input type="radio" name="mode_paiement" value="paiement_livraison" checked onclick="afficherMomo(false)"> Paiement à la livraison
        </label>
        <label style="display:flex; gap:6px; align-items:center;">
            <input type="radio" name="mode_paiement" value="mobile_money_mtn" onclick="afficherMomo('mtn')"> Mobile Money MTN
        </label>
        <label style="display:flex; gap:6px; align-items:center;">
            <input type="radio" name="mode_paiement" value="mobile_money_orange" onclick="afficherMomo('orange')"> Mobile Money Orange
        </label>
    </div>

    <div id="bloc-momo" style="display:none; background:#F4EFE4; border-radius:8px; padding:12px; margin:0 18px 14px;">
        <p style="font-size:13px; margin:0 0 8px;">
            Envoie le montant total au numéro <strong id="numero-momo"></strong> (<span id="operateur-momo"></span>),
            puis entre ci-dessous l'ID de transaction reçu par SMS.
        </p>
        <label class="champ" style="margin:0;">
            ID de transaction
            <input type="text" name="transaction_id" id="transaction_id" placeholder="Ex : MP240925.1234.A56789">
        </label>
    </div>

    <?php if ($erreur): ?><p class="erreur"><?= h($erreur) ?></p><?php endif; ?>

    <p class="note" style="margin-bottom:10px;">
        Un QR code de suivi sera généré après validation de ta commande — garde-le, il sera collé sur ton colis.
    </p>

    <button type="submit" class="btn-principal">Confirmer la commande</button>
</form>

<script>
const numeros = { mtn: '<?= h($settings['momo_mtn_numero']) ?>', orange: '<?= h($settings['momo_orange_numero']) ?>' };
const noms = { mtn: 'MTN Mobile Money', orange: 'Orange Money' };
function afficherMomo(operateur) {
    const bloc = document.getElementById('bloc-momo');
    if (!operateur) { bloc.style.display = 'none'; return; }
    bloc.style.display = 'block';
    document.getElementById('numero-momo').textContent = numeros[operateur];
    document.getElementById('operateur-momo').textContent = noms[operateur];
}
</script>

<?php $navActif = 'panier'; require 'partials/bottom-nav.php'; ?>
