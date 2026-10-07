<?php
require 'config.php';
require 'helpers.php';

$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();

$sessionId = session_id();
$stmt = $pdo->prepare("SELECT id FROM conversations WHERE session_id = :sid");
$stmt->execute(['sid' => $sessionId]);
$conversationId = $stmt->fetchColumn();

$historique = [];
if ($conversationId) {
    $h = $pdo->prepare("SELECT id, expediteur, contenu FROM messages WHERE conversation_id = :cid ORDER BY id ASC");
    $h->execute(['cid' => $conversationId]);
    $historique = $h->fetchAll();
}
$dernierId = !empty($historique) ? (int) end($historique)['id'] : 0;

$pageTitre = "Messagerie — " . $settings['brand_name'];
require 'partials/head.php';
?>

<div class="section-titre" style="margin-top:16px;">Messagerie</div>
<p class="note" style="margin-bottom:10px;">Pose ta question, notre assistant te répond — un conseiller prend le relais si besoin.</p>

<div id="chat-zone" style="padding:0 18px 14px; display:flex; flex-direction:column; gap:8px; min-height:200px;">
    <?php if (empty($historique)): ?>
        <div class="msg-bulle bot">Salut 👋 Comment je peux t'aider aujourd'hui ?</div>
    <?php else: ?>
        <?php foreach ($historique as $m): ?>
            <div class="msg-bulle <?= h($m['expediteur']) ?>"><?= nl2br(h($m['contenu'])) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
.msg-bulle { max-width: 80%; padding: 10px 14px; border-radius: 14px; font-size: 14px; line-height: 1.4; }
.msg-bulle.client { align-self: flex-end; background: var(--bleu); color: #fff; border-bottom-right-radius: 4px; }
.msg-bulle.bot { align-self: flex-start; background: #fff; border: 1px solid #eee; border-bottom-left-radius: 4px; }
.msg-bulle.agent { align-self: flex-start; background: #EDE6FB; border-bottom-left-radius: 4px; }
.chat-input-bar { position: fixed; bottom: 68px; left: 0; right: 0; background: #fff; border-top: 1px solid #e7e2d6; padding: 10px 18px; display: flex; gap: 8px; z-index: 25; }
.chat-input-bar input { flex: 1; padding: 11px; border-radius: 20px; border: 1px solid #ddd6c4; font-family: inherit; }
.chat-input-bar button { background: var(--bleu); color: #fff; border: none; border-radius: 20px; padding: 0 18px; font-weight: 700; cursor: pointer; }
</style>

<div class="chat-input-bar">
    <input type="text" id="chat-input" placeholder="Écris ton message…" autocomplete="off">
    <button id="chat-send">Envoyer</button>
</div>

<script>
(function () {
    const zone = document.getElementById('chat-zone');
    const input = document.getElementById('chat-input');
    const btn = document.getElementById('chat-send');
    let dernierId = <?= (int) $dernierId ?>;

    function ajouterBulle(expediteur, texte) {
        const div = document.createElement('div');
        div.className = 'msg-bulle ' + expediteur;
        div.textContent = texte;
        zone.appendChild(div);
        div.scrollIntoView({ block: 'end' });
    }

    function envoyer() {
        const texte = input.value.trim();
        if (!texte) return;
        ajouterBulle('client', texte);
        input.value = '';
        fetch('chat-send.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'message=' + encodeURIComponent(texte)
        })
        .then(r => r.json())
        .then(data => { if (data.reponse) ajouterBulle('bot', data.reponse); })
        .catch(() => ajouterBulle('bot', "Connexion impossible, réessaie."));
    }

    btn.addEventListener('click', envoyer);
    input.addEventListener('keydown', e => { if (e.key === 'Enter') envoyer(); });

    setInterval(() => {
        fetch('chat-poll.php?depuis=' + dernierId)
            .then(r => r.json())
            .then(data => {
                (data.messages || []).forEach(m => {
                    if (m.expediteur === 'agent') ajouterBulle('agent', m.contenu);
                    dernierId = Math.max(dernierId, parseInt(m.id, 10));
                });
            })
            .catch(() => {});
    }, 4000);
})();
</script>

<?php $navActif = 'messagerie'; require 'partials/bottom-nav.php'; ?>
