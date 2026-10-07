<?php
require 'config.php';
require 'helpers.php';

$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();
$erreur = '';
$onglet = $_GET['onglet'] ?? 'connexion';

// --- Déconnexion ---
if (isset($_GET['deconnexion'])) {
    unset($_SESSION['client_id'], $_SESSION['client_nom']);
    header('Location: compte.php');
    exit;
}

// --- Inscription ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'inscription') {
    $nom = trim($_POST['nom'] ?? '');
    $telephone = preg_replace('/[^0-9]/', '', $_POST['telephone'] ?? '');
    $mdp = $_POST['mot_de_passe'] ?? '';

    if ($nom === '' || $telephone === '' || strlen($mdp) < 4) {
        $erreur = "Nom, téléphone et un mot de passe d'au moins 4 caractères sont obligatoires.";
        $onglet = 'inscription';
    } else {
        $existe = $pdo->prepare("SELECT id FROM customers WHERE telephone = :t");
        $existe->execute(['t' => $telephone]);
        if ($existe->fetch()) {
            $erreur = "Un compte existe déjà avec ce numéro — connecte-toi plutôt.";
            $onglet = 'inscription';
        } else {
            $ins = $pdo->prepare(
                "INSERT INTO customers (nom, telephone, password_hash) VALUES (:nom, :tel, :ph) RETURNING id"
            );
            $ins->execute(['nom' => $nom, 'tel' => $telephone, 'ph' => password_hash($mdp, PASSWORD_DEFAULT)]);
            $customerId = $ins->fetchColumn();

            // Rattache automatiquement les commandes passées en invité avec ce même numéro.
            $pdo->prepare("UPDATE orders SET customer_id = :cid WHERE telephone = :tel AND customer_id IS NULL")
                ->execute(['cid' => $customerId, 'tel' => $telephone]);

            $_SESSION['client_id'] = $customerId;
            $_SESSION['client_nom'] = $nom;
            header('Location: compte.php');
            exit;
        }
    }
}

// --- Connexion ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'connexion') {
    $telephone = preg_replace('/[^0-9]/', '', $_POST['telephone'] ?? '');
    $mdp = $_POST['mot_de_passe'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM customers WHERE telephone = :t");
    $stmt->execute(['t' => $telephone]);
    $client = $stmt->fetch();

    if ($client && password_verify($mdp, $client['password_hash'])) {
        $_SESSION['client_id'] = $client['id'];
        $_SESSION['client_nom'] = $client['nom'];
        header('Location: compte.php');
        exit;
    }
    $erreur = "Numéro ou mot de passe incorrect.";
    $onglet = 'connexion';
}

$pageTitre = "Mon compte — " . $settings['brand_name'];
require 'partials/head.php';
?>

<?php if (!clientConnecte()): ?>

    <div class="onglets" style="margin:16px 18px 0;">
        <a class="onglet <?= $onglet === 'connexion' ? 'actif' : '' ?>" href="compte.php?onglet=connexion">Connexion</a>
        <a class="onglet <?= $onglet === 'inscription' ? 'actif' : '' ?>" href="compte.php?onglet=inscription">Créer un compte</a>
    </div>

    <?php if ($onglet === 'inscription'): ?>
        <form method="post" style="margin-top:16px;">
            <input type="hidden" name="action" value="inscription">
            <label class="champ">
                Nom complet
                <input type="text" name="nom" required value="<?= h($_POST['nom'] ?? '') ?>">
            </label>
            <label class="champ">
                Numéro de téléphone
                <input type="text" name="telephone" required value="<?= h($_POST['telephone'] ?? '') ?>" placeholder="6XX XXX XXX">
            </label>
            <label class="champ">
                Mot de passe
                <input type="password" name="mot_de_passe" required minlength="4">
            </label>
            <?php if ($erreur): ?><p class="erreur"><?= h($erreur) ?></p><?php endif; ?>
            <button type="submit" class="btn-principal">Créer mon compte</button>
        </form>
    <?php else: ?>
        <form method="post" style="margin-top:16px;">
            <input type="hidden" name="action" value="connexion">
            <label class="champ">
                Numéro de téléphone
                <input type="text" name="telephone" required value="<?= h($_POST['telephone'] ?? '') ?>" placeholder="6XX XXX XXX">
            </label>
            <label class="champ">
                Mot de passe
                <input type="password" name="mot_de_passe" required>
            </label>
            <?php if ($erreur): ?><p class="erreur"><?= h($erreur) ?></p><?php endif; ?>
            <button type="submit" class="btn-principal">Se connecter</button>
        </form>
    <?php endif; ?>

<?php else:
    $commandes = $pdo->prepare("SELECT * FROM orders WHERE customer_id = :cid ORDER BY date_commande DESC");
    $commandes->execute(['cid' => $_SESSION['client_id']]);
    $mesCommandes = $commandes->fetchAll();
    $itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :id");
?>
    <div class="section-titre" style="margin-top:16px; display:flex; justify-content:space-between; align-items:center;">
        <span>Bonjour <?= h($_SESSION['client_nom']) ?></span>
        <a href="compte.php?deconnexion=1" class="note" style="padding:0;">Déconnexion</a>
    </div>

    <div class="section-titre">Mes commandes</div>
    <?php if (empty($mesCommandes)): ?>
        <div class="vide">
            <p>Tu n'as pas encore de commande.</p>
            <p><a href="categorie.php" style="color:var(--bleu); font-weight:700;">Voir les outfits →</a></p>
        </div>
    <?php else: ?>
        <?php foreach ($mesCommandes as $cmd): $itemsStmt->execute(['id' => $cmd['id']]); $lignes = $itemsStmt->fetchAll();
            $total = array_sum(array_map(fn($l) => $l['prix_unitaire'] * $l['quantite'], $lignes));
        ?>
            <a href="suivi.php?commande=<?= (int) $cmd['id'] ?>&token=<?= h($cmd['qr_token']) ?>" class="ligne-panier" style="flex-direction:column; align-items:stretch; display:flex;">
                <div style="display:flex; justify-content:space-between;">
                    <strong style="font-size:13px;">Commande #<?= (int) $cmd['id'] ?></strong>
                    <span class="note" style="padding:0;"><?= h(date('d/m/Y', strtotime($cmd['date_commande']))) ?></span>
                </div>
                <div class="meta" style="margin-top:4px;">
                    <?= h(STATUTS_LIVRAISON[$cmd['statut']] ?? $cmd['statut']) ?> · <?= $total ?> FCFA
                </div>
                <div class="meta" style="margin-top:2px;">
                    <?= h(implode(', ', array_map(fn($l) => $l['titre_outfit'] . ' (x' . (int) $l['quantite'] . ')', $lignes))) ?>
                </div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>

<?php $navActif = ''; require 'partials/bottom-nav.php'; ?>
