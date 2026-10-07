<?php
require '../config.php';
require '../helpers.php';
requireLogin();

$erreur = '';
$v = ['titre' => '', 'categorie' => CATEGORIES[0], 'prix' => '', 'description' => '', 'conseils' => '', 'vedette' => false];
$stocksParTaille = array_fill_keys(TAILLES, 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v['titre'] = trim($_POST['titre'] ?? '');
    $v['categorie'] = $_POST['categorie'] ?? CATEGORIES[0];
    $v['prix'] = $_POST['prix'] ?? '';
    $v['description'] = trim($_POST['description'] ?? '');
    $v['conseils'] = trim($_POST['conseils'] ?? '');
    $v['vedette'] = isset($_POST['vedette']);
    foreach (TAILLES as $t) {
        $stocksParTaille[$t] = max(0, (int) ($_POST['stock'][$t] ?? 0));
    }
    $taillesAvecStock = array_filter($stocksParTaille, fn($s) => $s > 0);

    if ($v['titre'] === '' || !is_numeric($v['prix'])) {
        $erreur = "Le titre et le prix sont obligatoires.";
    } elseif (!in_array($v['categorie'], CATEGORIES, true)) {
        $erreur = "Catégorie invalide.";
    } elseif (empty($taillesAvecStock)) {
        $erreur = "Indique un stock supérieur à 0 pour au moins une taille.";
    } else {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            "INSERT INTO outfits (titre, categorie, tailles_disponibles, prix, description, conseils, vedette)
             VALUES (:titre, :cat, :tailles, :prix, :desc, :conseils, :vedette) RETURNING id"
        );
        $stmt->execute([
            'titre' => $v['titre'], 'cat' => $v['categorie'], 'tailles' => implode(',', array_keys($taillesAvecStock)),
            'prix' => (int) $v['prix'], 'desc' => $v['description'], 'conseils' => $v['conseils'],
            'vedette' => $v['vedette'] ? 't' : 'f',
        ]);
        $outfitId = $stmt->fetchColumn();

        $insStock = $pdo->prepare("INSERT INTO outfit_sizes (outfit_id, taille, stock) VALUES (:oid, :t, :s)");
        foreach ($stocksParTaille as $t => $s) {
            $insStock->execute(['oid' => $outfitId, 't' => $t, 's' => $s]);
        }

        if (!empty($_FILES['photos']['name'][0])) {
            $insImg = $pdo->prepare("INSERT INTO outfit_images (outfit_id, image_path, ordre) VALUES (:oid, :img, :ordre)");
            foreach ($_FILES['photos']['tmp_name'] as $i => $tmp) {
                if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
                [$nom, $err] = saveCompressedImage(['tmp_name' => $tmp, 'error' => $_FILES['photos']['error'][$i]], __DIR__ . '/../uploads');
                if ($err) continue;
                $insImg->execute(['oid' => $outfitId, 'img' => $nom, 'ordre' => $i]);
            }
        }

        $pdo->commit();
        header('Location: dashboard.php');
        exit;
    }
}

$ongletActif = 'ajouter';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ajouter un outfit</title>
<link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="admin-wrap">
    <div class="admin-header">
        <h2 style="font-size:22px; color:var(--bleu-fonce);">Espace vendeur</h2>
        <a href="logout.php" class="note" style="padding:0;">Déconnexion</a>
    </div>

    <?php require '_tabs.php'; ?>

    <form method="post" enctype="multipart/form-data">
        <label class="champ">
            Titre de l'outfit
            <input type="text" name="titre" required value="<?= h($v['titre']) ?>" placeholder="Ex : Ensemble jogging beige oversize">
        </label>

        <div class="champ-inline">
            <label class="champ">
                Catégorie
                <select name="categorie">
                    <?php foreach (CATEGORIES as $c): ?>
                        <option value="<?= h($c) ?>" <?= $v['categorie'] === $c ? 'selected' : '' ?>><?= h($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="champ">
                Prix (FCFA)
                <input type="number" name="prix" min="0" required value="<?= h((string) $v['prix']) ?>">
            </label>
        </div>

        <label class="champ">Stock par taille</label>
        <div class="checkbox-group">
            <?php foreach (TAILLES as $t): ?>
                <label style="flex-direction:column; align-items:flex-start; gap:2px;">
                    <?= h($t) ?>
                    <input type="number" name="stock[<?= h($t) ?>]" min="0" value="<?= (int) $stocksParTaille[$t] ?>" style="width:64px; padding:6px; border-radius:6px; border:1px solid #ddd6c4;">
                </label>
            <?php endforeach; ?>
        </div>

        <label class="champ">
            Les pièces de l'ensemble (description)
            <textarea name="description" rows="3" placeholder="Ex : T-shirt col rond beige + pantalon cargo noir"><?= h($v['description']) ?></textarea>
        </label>

        <label class="champ">
            Conseils de style (comment le porter, pour quelle occasion)
            <textarea name="conseils" rows="3" placeholder="Ex : Idéal en look décontracté du quotidien, avec des sneakers blanches."><?= h($v['conseils']) ?></textarea>
        </label>

        <label class="champ">
            Photos (plusieurs possibles — sur mannequin de préférence)
            <input type="file" name="photos[]" accept="image/*" multiple>
        </label>

        <div class="checkbox-group" style="padding-top:0;">
            <label><input type="checkbox" name="vedette" <?= $v['vedette'] ? 'checked' : '' ?>> Mettre en avant sur l'Accueil (best-seller)</label>
        </div>

        <?php if ($erreur): ?><p class="erreur"><?= h($erreur) ?></p><?php endif; ?>

        <button type="submit" class="btn-principal">Publier l'outfit</button>
    </form>
</div>
</body>
</html>
