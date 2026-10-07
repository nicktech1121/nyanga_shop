<?php
require 'config.php';
require 'helpers.php';

$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();
$pageTitre = "Service client — " . $settings['brand_name'];
require 'partials/head.php';
?>

<div class="stub">
    <div class="icone">🎧</div>
    <h2 style="color:var(--texte); font-size:18px;">Service client</h2>
    <p>Une question sur ta commande ou un outfit ?</p>
    <?php if (!empty($settings['whatsapp'])): ?>
        <p style="margin-top:16px;">
            <a href="https://wa.me/<?= h($settings['whatsapp']) ?>" style="color:var(--bleu); font-weight:700;" target="_blank" rel="noopener noreferrer">Écris-nous sur WhatsApp</a>
        </p>
    <?php endif; ?>
</div>

<?php $navActif = ''; require 'partials/bottom-nav.php'; ?>
