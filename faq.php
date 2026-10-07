<?php
require '../config.php';
require '../helpers.php';
requireLogin();

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'ajouter') {
        $mc = trim($_POST['mots_cles'] ?? '');
        $r = trim($_POST['reponse'] ?? '');
        if ($mc === '' || $r === '') {
            $erreur = "Les mots-clés et la réponse sont obligatoires.";
        } else {
            $ordreMax = (int) $pdo->query("SELECT COALESCE(MAX(ordre),0) FROM faq")->fetchColumn();
            $pdo->prepare("INSERT INTO faq (mots_cles, reponse, ordre) VALUES (:mc, :r, :o)")
                ->execute(['mc' => $mc, 'r' => $r, 'o' => $ordreMax + 1]);
        }
    } elseif ($action === 'supprimer') {
        $pdo->prepare("DELETE FROM faq WHERE id = :id")->execute(['id' => (int) $_POST['id']]);
    } elseif ($action === 'modifier') {
        $pdo->prepare("UPDATE faq SET mots_cles = :mc, reponse = :r WHERE id = :id")->execute([
            'mc' => trim($_POST['mots_cles'] ?? ''),
            'r' => trim($_POST['reponse'] ?? ''),
            'id' => (int) $_POST['id'],
        ]);
    }
    if (!$erreur) {
        header('Location: faq.php');
        exit;
    }
}

$faqs = $pdo->query("SELECT * FROM faq ORDER BY ordre")->fetchAll();
$ongletActif = 'faq';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FAQ du chatbot</title>
<link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="admin-wrap">
    <div class="admin-header">
        <h2 style="font-size:22px; color:var(--bleu-fonce);">Espace vendeur</h2>
        <a href="logout.php" class="note" style="padding:0;">Déconnexion</a>
    </div>

    <?php require '_tabs.php'; ?>

    <p class="note" style="padding:0; margin-bottom:14px;">
        Quand un client écrit un message contenant un de ces mots-clés (séparés par des virgules),
        le chatbot répond automatiquement. Sinon, la conversation est transmise à un conseiller
        dans l'onglet Messagerie.
    </p>

    <h3 style="font-size:15px; margin-bottom:8px;">Ajouter une réponse automatique</h3>
    <form method="post">
        <input type="hidden" name="action" value="ajouter">
        <label class="champ">
            Mots-clés (séparés par des virgules)
            <input type="text" name="mots_cles" required placeholder="livraison, delai, delais">
        </label>
        <label class="champ">
            Réponse automatique
            <textarea name="reponse" rows="2" required></textarea>
        </label>
        <?php if ($erreur): ?><p class="erreur"><?= h($erreur) ?></p><?php endif; ?>
        <button type="submit" class="btn-principal">Ajouter</button>
    </form>

    <h3 style="font-size:15px; margin:22px 0 8px;">Réponses existantes</h3>
    <?php if (empty($faqs)): ?>
        <p class="note" style="padding:0;">Aucune réponse configurée.</p>
    <?php else: ?>
        <?php foreach ($faqs as $f): ?>
            <div class="ligne-article" style="flex-direction:column; align-items:stretch; margin:0 0 10px;">
                <form method="post" style="display:flex; flex-direction:column; gap:6px;">
                    <input type="hidden" name="action" value="modifier">
                    <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                    <input type="text" name="mots_cles" value="<?= h($f['mots_cles']) ?>" style="padding:8px; border-radius:6px; border:1px solid #ddd6c4;">
                    <textarea name="reponse" rows="2" style="padding:8px; border-radius:6px; border:1px solid #ddd6c4; font-family:inherit;"><?= h($f['reponse']) ?></textarea>
                    <div style="display:flex; gap:8px;">
                        <button type="submit" class="icone-btn" style="border:1px solid var(--bleu); color:var(--bleu); border-radius:6px;">Enregistrer</button>
                    </div>
                </form>
                <form method="post" onsubmit="return confirm('Supprimer cette réponse ?');" style="margin-top:4px;">
                    <input type="hidden" name="action" value="supprimer">
                    <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                    <button type="submit" class="icone-btn" style="border:1px solid var(--alerte); color:var(--alerte); border-radius:6px;">Supprimer</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
