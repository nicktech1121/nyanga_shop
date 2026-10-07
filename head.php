<?php
/** À inclure après avoir chargé $settings. Variable optionnelle $pageTitre. */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitre ?? $settings['brand_name']) ?></title>
<link rel="stylesheet" href="<?= $cssPath ?? 'style.css' ?>">
</head>
<body>

<div class="topbar">
    <a class="logo" href="<?= $basePath ?? '' ?>index.php">Nyanga <span>Shop</span></a>
    <button class="menu-btn" onclick="document.getElementById('menu-overlay').classList.add('ouvert')" aria-label="Menu">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
</div>

<div class="menu-overlay" id="menu-overlay" onclick="if(event.target===this) this.classList.remove('ouvert')">
    <div class="menu-panel">
        <button class="menu-close" onclick="document.getElementById('menu-overlay').classList.remove('ouvert')">✕</button>
        <h3>Mon compte</h3>
        <a href="<?= $basePath ?? '' ?>compte.php"><?= clientConnecte() ? 'Bonjour ' . h($_SESSION['client_nom']) : 'Connexion / Créer un compte' ?></a>
        <a href="<?= $basePath ?? '' ?>service-client.php">Service client</a>
        <a href="<?= $basePath ?? '' ?>admin/login.php">Espace vendeur</a>
    </div>
</div>
