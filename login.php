<?php
require '../config.php';
require '../helpers.php';

$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hash = $pdo->query("SELECT pin_hash FROM settings WHERE id = 1")->fetchColumn();
    if ($hash && password_verify($_POST['pin'] ?? '', $hash)) {
        $_SESSION['admin'] = true;
        header('Location: dashboard.php');
        exit;
    }
    $erreur = 'Code incorrect, réessaie.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Espace vendeur — connexion</title>
<link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="login-box">
    <h2 style="font-size:22px; color:var(--bleu-fonce);">Espace vendeur</h2>
    <p class="note" style="padding:0;">Entre le code vendeur pour gérer les outfits.</p>
    <form method="post">
        <label class="champ" style="margin:14px 0;">
            <input type="password" name="pin" placeholder="Code" autofocus>
        </label>
        <?php if ($erreur): ?><p class="erreur" style="margin:0 0 12px;"><?= h($erreur) ?></p><?php endif; ?>
        <button type="submit" class="btn-principal" style="width:100%; margin:0;">Entrer</button>
    </form>
    <p style="margin-top:16px;"><a href="../index.php" class="note" style="padding:0;">&larr; Retour au site</a></p>
</div>
</body>
</html>
