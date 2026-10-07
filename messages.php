<?php
require '../config.php';
require '../helpers.php';
requireLogin();

$id = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cid = (int) ($_POST['conversation_id'] ?? 0);
    $reponse = trim($_POST['reponse'] ?? '');
    if ($reponse !== '') {
        $pdo->prepare("INSERT INTO messages (conversation_id, expediteur, contenu) VALUES (:cid, 'agent', :c)")
            ->execute(['cid' => $cid, 'c' => $reponse]);
        $pdo->prepare("UPDATE conversations SET necessite_agent = FALSE WHERE id = :cid")
            ->execute(['cid' => $cid]);
    }
    header('Location: messages.php?id=' . $cid);
    exit;
}

$conversations = $pdo->query("
    SELECT c.*, (SELECT contenu FROM messages WHERE conversation_id = c.id ORDER BY id DESC LIMIT 1) AS dernier_message
    FROM conversations c
    ORDER BY necessite_agent DESC, cree_le DESC
")->fetchAll();

$thread = [];
if ($id) {
    $t = $pdo->prepare("SELECT * FROM messages WHERE conversation_id = :id ORDER BY id ASC");
    $t->execute(['id' => $id]);
    $thread = $t->fetchAll();
}

$ongletActif = 'messages';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Messagerie — conversations clients</title>
<link rel="stylesheet" href="../style.css">
<style>
.conv-item { display:block; padding:10px 12px; border-radius:8px; margin-bottom:6px; background:#faf8f2; border:1px solid #eee; }
.conv-item.attente { border-color: var(--alerte); background:#FBEAE8; }
.bulle { max-width:85%; padding:8px 12px; border-radius:12px; font-size:13px; margin-bottom:6px; }
.bulle.client { background:#fff; border:1px solid #eee; }
.bulle.bot { background:#F3EFE4; margin-left:10%; }
.bulle.agent { background:var(--bleu); color:#fff; margin-left:10%; }
</style>
</head>
<body>
<div class="admin-wrap">
    <div class="admin-header">
        <h2 style="font-size:22px; color:var(--bleu-fonce);">Espace vendeur</h2>
        <a href="logout.php" class="note" style="padding:0;">Déconnexion</a>
    </div>

    <?php require '_tabs.php'; ?>

    <?php if (!$id): ?>
        <?php if (empty($conversations)): ?>
            <p class="note" style="padding:0;">Aucune conversation pour le moment.</p>
        <?php else: ?>
            <?php foreach ($conversations as $c): ?>
                <a class="conv-item <?= $c['necessite_agent'] ? 'attente' : '' ?>" href="messages.php?id=<?= (int) $c['id'] ?>">
                    <strong>Conversation #<?= (int) $c['id'] ?></strong>
                    <?= $c['necessite_agent'] ? ' — 🔴 en attente d\'un conseiller' : '' ?>
                    <div class="note" style="padding:4px 0 0;"><?= h(mb_strimwidth($c['dernier_message'] ?? '', 0, 80, '…')) ?></div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php else: ?>
        <p><a href="messages.php" class="note" style="padding:0;">&larr; Toutes les conversations</a></p>
        <div style="margin:14px 0;">
            <?php foreach ($thread as $m): ?>
                <div class="bulle <?= h($m['expediteur']) ?>"><?= nl2br(h($m['contenu'])) ?></div>
            <?php endforeach; ?>
        </div>
        <form method="post">
            <input type="hidden" name="conversation_id" value="<?= (int) $id ?>">
            <label class="champ">
                Répondre en tant que conseiller
                <textarea name="reponse" rows="3" required></textarea>
            </label>
            <button type="submit" class="btn-principal">Envoyer la réponse</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
