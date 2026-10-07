<?php
require '../config.php';
require '../helpers.php';
requireLogin();

$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();
$erreur = '';
$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brandName = trim($_POST['brand_name'] ?? '');
    $tagline = trim($_POST['tagline'] ?? '');
    $whatsapp = preg_replace('/[^0-9]/', '', $_POST['whatsapp'] ?? '');
    $momoMtn = preg_replace('/[^0-9]/', '', $_POST['momo_mtn_numero'] ?? '');
    $momoOrange = preg_replace('/[^0-9]/', '', $_POST['momo_orange_numero'] ?? '');
    $baseUrl = trim($_POST['base_url'] ?? '');
    $nouveauPin = trim($_POST['nouveau_pin'] ?? '');

    if ($brandName === '' || $whatsapp === '' || $baseUrl === '') {
        $erreur = "Le nom de la boutique, le numéro WhatsApp et l'adresse du site sont obligatoires.";
    } else {
        $champs = "brand_name=:bn, tagline=:tl, whatsapp=:wa, momo_mtn_numero=:mtn, momo_orange_numero=:orange, base_url=:url";
        $params = ['bn' => $brandName, 'tl' => $tagline, 'wa' => $whatsapp, 'mtn' => $momoMtn, 'orange' => $momoOrange, 'url' => $baseUrl];
        if ($nouveauPin !== '') {
            $champs .= ", pin_hash=:ph";
            $params['ph'] = password_hash($nouveauPin, PASSWORD_DEFAULT);
        }
        $pdo->prepare("UPDATE settings SET $champs WHERE id=1")->execute($params);
        $settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();
        $succes = "Réglages enregistrés.";
    }
}

$ongletActif = 'settings';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Réglages</title>
<link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="admin-wrap">
    <div class="admin-header">
        <h2 style="font-size:22px; color:var(--bleu-fonce);">Espace vendeur</h2>
        <a href="logout.php" class="note" style="padding:0;">Déconnexion</a>
    </div>

    <?php require '_tabs.php'; ?>

    <form method="post">
        <label class="champ">
            Nom de la boutique
            <input type="text" name="brand_name" required value="<?= h($settings['brand_name']) ?>">
        </label>
        <label class="champ">
            Accroche
            <input type="text" name="tagline" value="<?= h($settings['tagline']) ?>">
        </label>
        <label class="champ">
            Numéro WhatsApp (format international, sans le +)
            <input type="text" name="whatsapp" required value="<?= h($settings['whatsapp']) ?>" placeholder="237600000000">
        </label>
        <label class="champ">
            Numéro Mobile Money MTN (pour recevoir les paiements)
            <input type="text" name="momo_mtn_numero" value="<?= h($settings['momo_mtn_numero']) ?>" placeholder="237670000000">
        </label>
        <label class="champ">
            Numéro Orange Money (pour recevoir les paiements)
            <input type="text" name="momo_orange_numero" value="<?= h($settings['momo_orange_numero']) ?>" placeholder="237690000000">
        </label>
        <label class="champ">
            Adresse du site (pour les liens QR de suivi)
            <input type="text" name="base_url" required value="<?= h($settings['base_url']) ?>" placeholder="https://tondomaine.com/">
        </label>
        <p class="note" style="margin-top:-8px;">
            En local sur XAMPP, mets l'adresse IP de ton PC sur le réseau (ex : http://192.168.1.15/nyanga-shop/)
            pour que le QR code soit scannable depuis un téléphone — "localhost" ne fonctionne pas depuis un autre appareil.
        </p>
        <label class="champ">
            Changer le code vendeur (laisser vide pour ne pas changer)
            <input type="password" name="nouveau_pin" placeholder="Nouveau code">
        </label>

        <?php if ($erreur): ?><p class="erreur"><?= h($erreur) ?></p><?php endif; ?>
        <?php if ($succes): ?><p class="note" style="color:var(--succes); font-weight:600; padding:0;"><?= h($succes) ?></p><?php endif; ?>

        <button type="submit" class="btn-principal">Enregistrer</button>
    </form>
</div>
</body>
</html>
