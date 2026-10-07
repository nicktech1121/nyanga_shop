<?php
require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$sessionId = session_id();

$stmt = $pdo->prepare("SELECT * FROM conversations WHERE session_id = :sid");
$stmt->execute(['sid' => $sessionId]);
$conversation = $stmt->fetch();

if (!$conversation) {
    $ins = $pdo->prepare("INSERT INTO conversations (session_id) VALUES (:sid) RETURNING id");
    $ins->execute(['sid' => $sessionId]);
    $conversationId = $ins->fetchColumn();
} else {
    $conversationId = $conversation['id'];
}

$texte = trim($_POST['message'] ?? '');
if ($texte === '') {
    http_response_code(400);
    echo json_encode(['erreur' => 'Message vide']);
    exit;
}

$pdo->prepare("INSERT INTO messages (conversation_id, expediteur, contenu) VALUES (:cid, 'client', :c)")
    ->execute(['cid' => $conversationId, 'c' => $texte]);

$texteNormalise = mb_strtolower($texte, 'UTF-8');
$faqs = $pdo->query("SELECT * FROM faq ORDER BY ordre")->fetchAll();

$meilleure = null;
$meilleurScore = 0;
foreach ($faqs as $f) {
    $mots = array_filter(array_map('trim', explode(',', mb_strtolower($f['mots_cles'], 'UTF-8'))));
    $score = 0;
    foreach ($mots as $mot) {
        if ($mot !== '' && mb_strpos($texteNormalise, $mot) !== false) {
            $score++;
        }
    }
    if ($score > $meilleurScore) {
        $meilleurScore = $score;
        $meilleure = $f;
    }
}

if ($meilleure) {
    $reponse = $meilleure['reponse'];
    $pdo->prepare("INSERT INTO messages (conversation_id, expediteur, contenu) VALUES (:cid, 'bot', :c)")
        ->execute(['cid' => $conversationId, 'c' => $reponse]);
} else {
    $reponse = "Je n'ai pas de réponse automatique à ça pour l'instant — je transmets ta question à un conseiller, il te répond bientôt ici même.";
    $pdo->prepare("INSERT INTO messages (conversation_id, expediteur, contenu) VALUES (:cid, 'bot', :c)")
        ->execute(['cid' => $conversationId, 'c' => $reponse]);
    $pdo->prepare("UPDATE conversations SET necessite_agent = TRUE WHERE id = :cid")
        ->execute(['cid' => $conversationId]);
}

echo json_encode(['reponse' => $reponse]);
