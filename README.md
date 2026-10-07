# Nyanga Shop — PHP + PostgreSQL pour XAMPP

## 1. Copier le projet
Copie tout le dossier `nyanga-shop` dans `C:\xampp\htdocs\nyanga-shop`.

## 2. Base de données
1. Crée une base PostgreSQL nommée `nyanga_shop` (via pgAdmin)
2. Mets tes identifiants dans `config.php`

## 3. Extensions PHP nécessaires
Dans `C:\xampp\php\php.ini`, décommente (enlève le `;`) ces lignes puis
redémarre Apache :
```
extension=pdo_pgsql
extension=pgsql
extension=gd
```

## 4. Installer/mettre à jour les tables
Ouvre dans le navigateur :
```
http://localhost/nyanga-shop/install.php
```
Rejouer ce script est sans danger, même après une mise à jour du site —
il ajoute juste ce qui manque. Code vendeur par défaut : **2580**.
Supprime `install.php` une fois que tout fonctionne.

## 5. Pages du site
- Accueil : `index.php` — Catégorie : `categorie.php` — Fiche : `outfit.php?id=1`
- Panier : `panier.php` — Suivi commande : `suivi.php?commande=X&token=...`
- Espace vendeur : `admin/login.php`

## 6. Ce qui est fonctionnel aujourd'hui
- **Structure app mobile** : barre du haut + menu latéral, nav du bas
  (Catégorie / Messagerie / Panier)
- **Catalogue** : Accueil (best-sellers), Catégorie (filtres), fiche outfit
  (galerie photo, pièces, conseils de style, une seule taille pour
  l'ensemble, achat direct ou via panier)
- **Panier + tunnel de commande** : nom, quartier, téléphone
- **Chatbot + Messagerie** : réponses automatiques par mots-clés
  (configurables dans l'espace vendeur → FAQ chatbot), bascule vers un
  conseiller humain si aucune réponse ne correspond (espace vendeur →
  Messagerie, avec alerte 🔴)
- **Paiement Mobile Money (vérification manuelle)** : au checkout, le
  client choisit MTN, Orange ou paiement à la livraison ; pour le Mobile
  Money, il envoie l'argent au numéro affiché (réglable dans Réglages) et
  entre l'ID de transaction reçu par SMS ; le vendeur valide dans
  Commandes → statut de paiement
- **QR code de suivi** : généré à chaque commande, affiché à la
  confirmation, imprimable sur une étiquette (Commandes → "🖨 Étiquette
  QR") ; scanné, il ouvre `suivi.php` qui montre le statut
  (préparation → expédié → livré) et le contenu de la commande
- **Espace vendeur** : gestion des outfits (photos multiples, tailles,
  mise en avant), commandes (statut livraison + paiement), FAQ du
  chatbot, conversations clients, réglages (marque, WhatsApp, numéros
  Mobile Money, adresse du site)

## 7. Important — paiement Mobile Money automatique
Le paiement est aujourd'hui **vérifié manuellement** : il n'y a pas
d'intégration automatique avec MTN ou Orange, parce que ça nécessite un
**compte marchand** obtenu séparément (contrat avec l'opérateur, ou un
agrégateur comme CinetPay, Flutterwave ou PawaPay). Une fois ce compte
obtenu, l'appel réel se branche dans la fonction
`initierPaiementMobileMoney()` du fichier `helpers.php` — le reste du
site (checkout, confirmation, suivi) n'a pas besoin de changer.

## 8. Important — adresse du site pour les QR codes
Les QR codes encodent un lien vers `suivi.php`. Pour qu'un téléphone
puisse le scanner, l'adresse du site doit être **joignable depuis
l'extérieur** :
- En test local sur XAMPP : utilise l'adresse IP de ton PC sur le réseau
  Wi-Fi (ex : `http://192.168.1.15/nyanga-shop/`), à régler dans
  Réglages → "Adresse du site". `localhost` ne fonctionnera pas depuis
  un autre appareil.
- En production : ce sera ton nom de domaine réel.

La génération de l'image QR elle-même utilise une API publique gratuite
(qrserver.com) — il faut donc une connexion internet côté vendeur pour
l'afficher/l'imprimer.

## 9. Back-office complet (dernière phase)
- **Stock réel par taille** : à la création/modification d'un outfit, le
  vendeur indique une quantité par taille (plus de simple case à cocher).
  Le stock se décrémente automatiquement à chaque commande validée, et
  une commande est bloquée si le stock est insuffisant au moment du
  paiement
- **admin/stock.php** : vue rapide de tous les outfits avec leurs
  quantités par taille, modifiable en un clic ; rouge = rupture (0),
  orange = stock bas (≤ 2)
- **admin/statistiques.php** : chiffre d'affaires total et déjà payé,
  nombre de commandes, panier moyen, top 5 des outfits les plus vendus,
  ventes par catégorie, répartition des commandes par statut
  (livraison et paiement), alerte ruptures de stock
- Le catalogue public (Accueil, Catégorie, fiche outfit) n'affiche et ne
  propose à l'achat que les tailles réellement en stock

## 11. Espace client
- Inscription simple : nom + téléphone + mot de passe (`compte.php`)
- Connexion avec le même numéro + mot de passe
- Une fois connecté : historique des commandes avec statut de livraison,
  total et détail, cliquables pour aller vers le suivi complet
- Les commandes passées en invité (sans compte) avec le même numéro de
  téléphone sont automatiquement rattachées au compte à la création
- Le menu (☰ en haut à droite) affiche "Bonjour [nom]" une fois connecté,
  avec un lien de déconnexion
- Au checkout, le nom et le téléphone sont pré-remplis si le client est
  connecté — la commande en tant qu'invité reste possible, pas besoin de
  compte pour acheter

## 10. Mise en ligne réelle
XAMPP ne fonctionne qu'en local. Pour que les clients y accèdent, il
faudra un hébergement supportant PHP + PostgreSQL, et un vrai nom de
domaine (indispensable aussi pour les QR codes). On verra ça le moment
venu.
