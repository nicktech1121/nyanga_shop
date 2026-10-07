<?php
/** Nécessite $pdo et $ongletActif (une des clés ci-dessous). */
$nbEnAttente = (int) $pdo->query("SELECT COUNT(*) FROM conversations WHERE necessite_agent = TRUE")->fetchColumn();
$nbRuptures = (int) $pdo->query("
    SELECT COUNT(DISTINCT o.id) FROM outfits o
    WHERE o.vendu = FALSE AND NOT EXISTS (SELECT 1 FROM outfit_sizes s WHERE s.outfit_id = o.id AND s.stock > 0)
")->fetchColumn();
$onglets = [
    'dashboard' => ['dashboard.php', 'Mes outfits'],
    'ajouter' => ['add.php', 'Ajouter'],
    'stock' => ['stock.php', 'Stock' . ($nbRuptures > 0 ? ' ⚠️' : '')],
    'commandes' => ['commandes.php', 'Commandes'],
    'statistiques' => ['statistiques.php', 'Statistiques'],
    'messages' => ['messages.php', 'Messagerie' . ($nbEnAttente > 0 ? ' 🔴' : '')],
    'faq' => ['faq.php', 'FAQ chatbot'],
    'settings' => ['settings.php', 'Réglages'],
];
?>
<div class="onglets">
    <?php foreach ($onglets as $key => [$href, $label]): ?>
        <a class="onglet <?= $ongletActif === $key ? 'actif' : '' ?>" href="<?= $href ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>
