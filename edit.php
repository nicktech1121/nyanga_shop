<?php
require '../config.php';
require '../helpers.php';
requireLogin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM outfits WHERE id = :id");
$stmt->execute(['id' => $id]);
$outfit = $stmt->fetch();

if (!$outfit) {
    header('Location: dashboard.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'supprimer_photo') {
    $imgId = (int) ($_POST['image_id'] ?? 0);
    $img = $pdo->prepare("SELECT image_path FROM outfit_images WHERE id = :id AND outfit_id = :oid");
    $img->execute(['id' => $imgId, 'oid' => $id]);
    $chemin = $img->fetchColumn();
    if ($chemin) {
        deleteImageFile($chemin, __DIR__ . '/../uploads');
        $pdo->prepare("DELETE FROM outfit_images WHERE id = :id")->execute(['id' => $imgId]);
    }
    header('Location: edit.php?id=' . $id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'supprimer_photo') {
    $titre = trim($_POST['titre'] ?? '');
    $categorie = $_POST['categorie'] ?? CATEGORIES[0];
    $prix = $_POST['prix'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $conseils = trim($_POST['conseils'] ?? '');
    $vedette = isset($_POST['vedette']);
    $stocksParTaille = [];
    foreach (TAILLES as $t) {
        $stocksParTaille[$t] = max(0, (int) ($_POST['stock'][$t] ?? 0));
    }
    $taillesAvecStock = array_filter($stocksParTaille, fn($s) => $s > 0);

    if ($titre === '' || !is_numeric($prix)) {
        $erreur = "Le titre et le prix sont obligatoires.";
    } elseif (empty($taillesAvecStock)) {
        $erreur = "Indique un stock supérieur à 0 pour au moins une taille.";
    } else {
        $pdo->prepare(
            "UPDATE outfits SET titre=:titre, categorie=:cat, tailles_disponibles=:tailles,
             prix=:prix, description=:desc, conseils=:conseils, vedette=:vedette WHERE id=:id"
        )->execute([
            'titre' => $titre, 'cat' => $categorie, 'tailles' => implode(',', array_keys($taillesAvecStock)),
            'prix' => (int) $prix, 'desc' => $description, 'conseils' => $conseils,
            'vedette' => $vedette ? 't' : 'f', 'id' => $id,
        ]);

        $upsertStock = $pdo->prepare(
            "INSERT INTO outfit_sizes (outfit_id, taille, stock) VALUES (:oid, :t, :s)
             ON CONFLICT (outfit_id, taille) DO UPDATE SET stock = :s"
        );
        foreach ($stocksParTaille as $t => $s) {
            $upsertStock->execute(['oid' => $id, 't' => $t, 's' => $s]);
        }

        if (!empty($_FILES['photos']['name'][0])) {
            $max = (int) $pdo->query("SELECT COALESCE(MAX(ordre),0) FROM outfit_images WHERE outfit_id = $id")->fetchColumn();
            $insImg = $pdo->prepare("INSERT INTO outfit_images (outfit_id, image_path, ordre) VALUES (:oid, :img, :ordre)");
            foreach ($_FILES['photos']['tmp_name'] as $i => $tmp) {
                if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
                [$nom, $err] = saveCompressedImage(['tmp_name' => $tmp, 'error' => $_FILES['photos']['error'][$i]], __DIR__ . '/../uploads');
                if ($err) continue;
                $insImg->execute(['oid' => $id, 'img' => $nom, 'ordre' => $max + $i + 1]);
            }
        }

        header('Location: dashboard.php');
        exit;
    }
}

$imgs = $pdo->prepare("SELECT * FROM outfit_images WHERE outfit_id = :id ORDER BY ordre");
$imgs->execute(['id' => $id]);
$images = $imgs->fetchAll();

$stocksActuels = array_fill_keys(TAILLES, 0);
$stockRows = $pdo->prepare("SELECT taille, stock FROM outfit_sizes WHERE outfit_id = :id");
$stockRows->execute(['id' => $id]);
foreach ($stockRows->fetchAll() as $row) {
    $stocksActuels[$row['taille']] = (int) $row['stock'];
}
$ongletActif = 'dashboard';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Modifier l'outfit</title>
<link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="admin-wrap">
    <div class="admin-header">
        <h2 style="font-size:22px; color:var(--bleu-fonce);">Espace vendeur</h2>
        <a href="logout.php" class="note" style="padding:0;">Déconnexion</a>
    </div>

    <?php require '_tabs.php'; ?>

    <?php if (!empty($images)): ?>
        <label class="champ" style="margin-bottom:6px;">Photos actuelles</label>
        <div class="miniatures-existantes">
            <?php foreach ($images as $img): ?>
                <div style="position:relative;">
                    <img src="../uploads/<?= h($img['image_path']) ?>" alt="">
                    <form method="post" style="position:absolute; top:-6px; right:-6px;" onsubmit="return confirm('Retirer cette photo ?');">
                        <input type="hidden" name="action" value="supprimer_photo">
                        <input type="hidden" name="image_id" value="<?= (int) $img['id'] ?>">
                        <button class="icone-btn" style="background:#fff; border-radius:50%; width:20px; height:20px; padding:0; font-size:11px; color:var(--alerte); box-shadow:0 1px 3px rgba(0,0,0,0.3);">✕</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= (int) $id ?>">

        <label class="champ">
            Titre de l'outfit
            <input type="text" name="titre" required value="<?= h($outfit['titre']) ?>">
        </label>

        <div class="champ-inline">
            <label class="champ">
                Catégorie
                <select name="categorie">
                    <?php foreach (CATEGORIES as $c): ?>
                        <option value="<?= h($c) ?>" <?= $outfit['categorie'] === $c ? 'selected' : '' ?>><?= h($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="champ">
                Prix (FCFA)
                <input type="number" name="prix" min="0" required value="<?= h((string) $outfit['prix']) ?>">
            </label>
        </div>

        <label class="champ">Stock par taille</label>
        <div class="checkbox-group">
            <?php foreach (TAILLES as $t): ?>
                <label style="flex-direction:column; align-items:flex-start; gap:2px;">
                    <?= h($t) ?>
                    <input type="number" name="stock[<?= h($t) ?>]" min="0" value="<?= (int) $stocksActuels[$t] ?>" style="width:64px; padding:6px; border-radius:6px; border:1px solid #ddd6c4;">
                </label>
            <?php endforeach; ?>
        </div>

        <label class="champ">
            Les pièces de l'ensemble
            <textarea name="description" rows="3"><?= h($outfit['description']) ?></textarea>
        </label>

        <label class="champ">
            Conseils de style
            <textarea name="conseils" rows="3"><?= h($outfit['conseils']) ?></textarea>
        </label>

        <label class="champ">
            Ajouter d'autres photos (optionnel)
            <input type="file" name="photos[]" accept="image/*" multiple>
        </label>

        <div class="checkbox-group" style="padding-top:0;">
            <label><input type="checkbox" name="vedette" <?= $outfit['vedette'] ? 'checked' : '' ?>> Mettre en avant sur l'Accueil</label>
        </div>

        <?php if ($erreur): ?><p class="erreur"><?= h($erreur) ?></p><?php endif; ?>

        <button type="submit" class="btn-principal">Enregistrer les modifications</button>
    </form>
</div>
</body>
</html>
