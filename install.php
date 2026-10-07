<?php
// À ouvrir dans le navigateur pour créer/mettre à jour les tables.
// Rejouer ce script est sans danger (IF NOT EXISTS partout).
require 'config.php';

$pdo->exec("
    CREATE TABLE IF NOT EXISTS outfits (
        id SERIAL PRIMARY KEY,
        titre VARCHAR(150) NOT NULL,
        categorie VARCHAR(30) NOT NULL,
        tailles_disponibles VARCHAR(50) NOT NULL DEFAULT 'S,M,L,XL',
        prix INTEGER NOT NULL,
        description TEXT,
        conseils TEXT,
        vedette BOOLEAN NOT NULL DEFAULT FALSE,
        vendu BOOLEAN NOT NULL DEFAULT FALSE,
        date_ajout TIMESTAMP NOT NULL DEFAULT NOW()
    );
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS outfit_sizes (
        id SERIAL PRIMARY KEY,
        outfit_id INTEGER NOT NULL REFERENCES outfits(id) ON DELETE CASCADE,
        taille VARCHAR(10) NOT NULL,
        stock INTEGER NOT NULL DEFAULT 0,
        UNIQUE (outfit_id, taille)
    );
");

// Migration : les outfits créés avant cette phase n'ont pas de stock détaillé
// par taille (juste une liste de tailles "disponibles"). On leur donne un
// stock de départ de 5 par taille pour qu'ils restent visibles sur le site.
$outfitsSansStock = $pdo->query("
    SELECT o.id, o.tailles_disponibles FROM outfits o
    WHERE NOT EXISTS (SELECT 1 FROM outfit_sizes s WHERE s.outfit_id = o.id)
")->fetchAll();
if (!empty($outfitsSansStock)) {
    $insStock = $pdo->prepare("INSERT INTO outfit_sizes (outfit_id, taille, stock) VALUES (:oid, :t, 5) ON CONFLICT DO NOTHING");
    foreach ($outfitsSansStock as $o) {
        foreach (array_filter(array_map('trim', explode(',', $o['tailles_disponibles']))) as $t) {
            $insStock->execute(['oid' => $o['id'], 't' => $t]);
        }
    }
}

$pdo->exec("
    CREATE TABLE IF NOT EXISTS outfit_images (
        id SERIAL PRIMARY KEY,
        outfit_id INTEGER NOT NULL REFERENCES outfits(id) ON DELETE CASCADE,
        image_path VARCHAR(255) NOT NULL,
        ordre INTEGER NOT NULL DEFAULT 0
    );
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS orders (
        id SERIAL PRIMARY KEY,
        nom_client VARCHAR(150) NOT NULL,
        quartier VARCHAR(150) NOT NULL,
        telephone VARCHAR(30) NOT NULL,
        statut VARCHAR(20) NOT NULL DEFAULT 'en_preparation',
        date_commande TIMESTAMP NOT NULL DEFAULT NOW()
    );
");

// Colonnes paiement Mobile Money + QR de suivi.
$pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS qr_token VARCHAR(40);");
$pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS mode_paiement VARCHAR(30) NOT NULL DEFAULT 'paiement_livraison';");
$pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS transaction_id VARCHAR(60);");
$pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS statut_paiement VARCHAR(20) NOT NULL DEFAULT 'non_paye';");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS order_items (
        id SERIAL PRIMARY KEY,
        order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
        outfit_id INTEGER REFERENCES outfits(id) ON DELETE SET NULL,
        titre_outfit VARCHAR(150) NOT NULL,
        taille VARCHAR(10) NOT NULL,
        prix_unitaire INTEGER NOT NULL,
        quantite INTEGER NOT NULL
    );
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS customers (
        id SERIAL PRIMARY KEY,
        nom VARCHAR(150) NOT NULL,
        telephone VARCHAR(30) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        cree_le TIMESTAMP NOT NULL DEFAULT NOW()
    );
");

$pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS customer_id INTEGER REFERENCES customers(id);");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS conversations (
        id SERIAL PRIMARY KEY,
        session_id VARCHAR(100) UNIQUE NOT NULL,
        necessite_agent BOOLEAN NOT NULL DEFAULT FALSE,
        cree_le TIMESTAMP NOT NULL DEFAULT NOW()
    );
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS messages (
        id SERIAL PRIMARY KEY,
        conversation_id INTEGER NOT NULL REFERENCES conversations(id) ON DELETE CASCADE,
        expediteur VARCHAR(10) NOT NULL CHECK (expediteur IN ('client', 'bot', 'agent')),
        contenu TEXT NOT NULL,
        envoye_le TIMESTAMP NOT NULL DEFAULT NOW()
    );
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS faq (
        id SERIAL PRIMARY KEY,
        mots_cles VARCHAR(255) NOT NULL,
        reponse TEXT NOT NULL,
        ordre INTEGER NOT NULL DEFAULT 0
    );
");

$nbFaq = (int) $pdo->query("SELECT COUNT(*) FROM faq")->fetchColumn();
if ($nbFaq === 0) {
    $seed = $pdo->prepare("INSERT INTO faq (mots_cles, reponse, ordre) VALUES (:mc, :r, :o)");
    $exemples = [
        ['livraison,livrer,delai,delais', "On livre à Douala/Yaoundé sous 24 à 48h, et dans les autres villes via agence de voyage sous 2 à 4 jours.", 1],
        ['taille,pointure,mesure', "Chaque outfit indique ses tailles disponibles sur sa fiche. En cas de doute entre deux tailles, prends la plus grande.", 2],
        ['prix,paiement,payer,mobile money', "Tu peux payer par Mobile Money (MTN ou Orange) au moment de la commande, ou à la livraison.", 3],
        ['retour,echange,rembours', "Les échanges sont possibles sous 48h après livraison si l'outfit n'a pas été porté, contacte-nous avec ton numéro de commande.", 4],
        ['bonjour,salut,bonsoir', "Salut 👋 Bienvenue chez nous ! Dis-moi ce que tu cherches ou pose ta question.", 5],
    ];
    foreach ($exemples as $e) {
        $seed->execute(['mc' => $e[0], 'r' => $e[1], 'o' => $e[2]]);
    }
}

$pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
        id INTEGER PRIMARY KEY DEFAULT 1,
        brand_name VARCHAR(120) NOT NULL DEFAULT 'Nyanga Shop',
        tagline VARCHAR(255) NOT NULL DEFAULT 'Outfits complets, stylés, livrés chez toi',
        whatsapp VARCHAR(20) NOT NULL DEFAULT '237600000000',
        pin_hash VARCHAR(255) NOT NULL,
        CONSTRAINT single_row CHECK (id = 1)
    );
");

$pdo->exec("ALTER TABLE settings ADD COLUMN IF NOT EXISTS momo_mtn_numero VARCHAR(20) DEFAULT '237670000000';");
$pdo->exec("ALTER TABLE settings ADD COLUMN IF NOT EXISTS momo_orange_numero VARCHAR(20) DEFAULT '237690000000';");
$pdo->exec("ALTER TABLE settings ADD COLUMN IF NOT EXISTS base_url VARCHAR(255) DEFAULT 'http://localhost/nyanga-shop/';");

$count = (int) $pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();

if ($count === 0) {
    $hash = password_hash('2580', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO settings (id, pin_hash) VALUES (1, :hash)");
    $stmt->execute(['hash' => $hash]);
    echo "<p>Installation terminée. Tables créées.</p>";
    echo "<p><strong>Code vendeur par défaut : 2580</strong> — connecte-toi sur "
       . "<a href='admin/login.php'>admin/login.php</a> puis change-le tout de "
       . "suite dans Réglages.</p>";
} else {
    echo "<p>La base est déjà installée — tables/colonnes mises à jour si besoin.</p>";
}

echo "<p style='color:#B0402A'>Supprime ce fichier (install.php) une fois l'installation terminée.</p>";
