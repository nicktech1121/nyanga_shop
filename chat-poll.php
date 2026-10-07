<?php
require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$sessionId = session_id();
$depuisId = (int) ($_GET['depuis'] ?? 0);

$stmt = $pdo->prepare("SELECT id FROM conversations WHERE session_id = :sid");
$stmt->execute(['sid' => $sessionId]);
$conversationId = $stmt->fetchColumn();

if (!$conversationId) {
    echo json_encode(['messages' => []]);
    exit;
}

$msgs = $pdo->prepare(
    "SELECT id, expediteur, contenu, envoye_le FROM messages
     WHERE conversation_id = :cid AND id > :depuis
     ORDER BY id ASC"
);
$msgs->execute(['cid' => $conversationId, 'depuis' => $depuisId]);

echo json_encode(['messages' => $msgs->fetchAll()]);
