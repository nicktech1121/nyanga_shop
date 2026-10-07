<?php
const CATEGORIES = ['Casual', 'Streetwear', 'Sport', 'Chic', 'Vintage'];
const TAILLES = ['S', 'M', 'L', 'XL', 'XXL'];

function h(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function requireLogin(): void
{
    if (empty($_SESSION['admin'])) {
        header('Location: login.php');
        exit;
    }
}

function clientConnecte(): bool
{
    return !empty($_SESSION['client_id']);
}

/**
 * Redimensionne et compresse une image uploadée, puis l'enregistre en JPEG
 * dans $destDir. Retourne [nomDeFichier, null] ou [null, messageErreur].
 */
function saveCompressedImage(array $file, string $destDir, int $maxWidth = 900, int $quality = 78): array
{
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return [null, "Erreur lors de l'envoi du fichier."];
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return [null, "Le fichier envoyé n'est pas une image valide."];
    }

    [$width, $height, $type] = $info;

    switch ($type) {
        case IMAGETYPE_JPEG:
            $src = imagecreatefromjpeg($file['tmp_name']);
            break;
        case IMAGETYPE_PNG:
            $src = imagecreatefrompng($file['tmp_name']);
            break;
        case IMAGETYPE_WEBP:
            $src = imagecreatefromwebp($file['tmp_name']);
            break;
        default:
            return [null, "Formats acceptés : JPG, PNG ou WEBP."];
    }

    if (!$src) {
        return [null, "Impossible de lire cette image."];
    }

    $scale = min(1, $maxWidth / $width);
    $newW = max(1, (int) round($width * $scale));
    $newH = max(1, (int) round($height * $scale));

    $dst = imagecreatetruecolor($newW, $newH);
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefill($dst, 0, 0, $white);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);

    $filename = uniqid('outfit_', true) . '.jpg';
    $ok = imagejpeg($dst, rtrim($destDir, '/') . '/' . $filename, $quality);

    imagedestroy($src);
    imagedestroy($dst);

    if (!$ok) {
        return [null, "Impossible d'enregistrer l'image sur le serveur."];
    }

    return [$filename, null];
}

function deleteImageFile(?string $filename, string $uploadsDir): void
{
    if ($filename && is_file(rtrim($uploadsDir, '/') . '/' . $filename)) {
        @unlink(rtrim($uploadsDir, '/') . '/' . $filename);
    }
}

// ---------------- Panier (session) ----------------
// $_SESSION['panier'][outfit_id] = ['taille' => 'M', 'quantite' => 2]

function panierAjouter(int $outfitId, string $taille, int $quantite = 1): void
{
    if (!isset($_SESSION['panier'])) {
        $_SESSION['panier'] = [];
    }
    if (isset($_SESSION['panier'][$outfitId])) {
        $_SESSION['panier'][$outfitId]['quantite'] += $quantite;
        $_SESSION['panier'][$outfitId]['taille'] = $taille;
    } else {
        $_SESSION['panier'][$outfitId] = ['taille' => $taille, 'quantite' => $quantite];
    }
}

function panierCompte(): int
{
    if (empty($_SESSION['panier'])) {
        return 0;
    }
    return array_sum(array_column($_SESSION['panier'], 'quantite'));
}

// ---------------- QR code & paiement ----------------

const STATUTS_LIVRAISON = [
    'en_preparation' => 'En préparation',
    'expedie' => 'Expédié',
    'livre' => 'Livré',
];

const STATUTS_PAIEMENT = [
    'non_paye' => 'Non payé (paiement à la livraison)',
    'en_verification' => 'En attente de vérification',
    'paye' => 'Payé',
    'rejete' => 'Paiement rejeté',
];

function genererQrToken(): string
{
    return bin2hex(random_bytes(12));
}

/**
 * URL de l'image QR (via une API publique gratuite — nécessite une connexion
 * internet côté vendeur pour l'afficher/l'imprimer). Encode le lien de suivi
 * de la commande.
 */
function urlQrCode(string $lienSuivi, int $taille = 220): string
{
    return "https://api.qrserver.com/v1/create-qr-code/?size={$taille}x{$taille}&data=" . urlencode($lienSuivi);
}

function lienSuivi(string $baseUrl, int $orderId, string $token): string
{
    return rtrim($baseUrl, '/') . '/suivi.php?commande=' . $orderId . '&token=' . $token;
}

/**
 * ---------------------------------------------------------------
 * PLACEHOLDER — intégration automatique Mobile Money (phase future)
 * ---------------------------------------------------------------
 * Aujourd'hui, le paiement Mobile Money est VÉRIFIÉ MANUELLEMENT : le client
 * envoie l'argent au numéro du vendeur puis saisit l'ID de transaction reçu
 * par SMS ; le vendeur confirme dans l'espace vendeur (admin/commandes.php).
 *
 * Le jour où un compte marchand est obtenu (MTN MoMo API, Orange Money API,
 * ou un agrégateur comme CinetPay/Flutterwave/PawaPay), cette fonction est
 * l'endroit où brancher l'appel réel — elle doit alors :
 *   1. Appeler l'API du fournisseur avec le montant + le numéro du client
 *   2. Retourner un statut immédiat (paye / en_attente / echec)
 *   3. Gérer le webhook de confirmation du fournisseur séparément
 * Le reste du site (checkout, confirmation, suivi) n'aura pas besoin de
 * changer : il lit juste `statut_paiement` en base.
 */
function initierPaiementMobileMoney(string $operateur, string $numeroClient, int $montant): array
{
    return ['statut' => 'en_verification', 'reference' => null];
}
