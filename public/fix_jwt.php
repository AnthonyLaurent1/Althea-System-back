<?php
header('Content-Type: text/plain; charset=utf-8');
echo "--- Début de la régénération via Web ---\n";

$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    die("ERREUR : Fichier .env introuvable au chemin : " . realpath($envPath) . "\n");
}

$envContent = file_get_contents($envPath);
$passphrase = null;
if (preg_match('/JWT_PASSPHRASE\s*=\s*["\']?([a-zA-Z0-9]+)["\']?/', $envContent, $matches)) {
    $passphrase = $matches[1];
}

if (!$passphrase) {
    die("ERREUR : Impossible de lire JWT_PASSPHRASE dans le fichier .env\n");
}

echo "Passphrase trouvée dans le .env : " . substr($passphrase, 0, 8) . "...\n";

// Configuration OpenSSL
$config = [
    "digest_alg" => "sha512",
    "private_key_bits" => 4096,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
];

echo "Génération de la ressource OpenSSL...\n";
$res = openssl_pkey_new($config);
if (!$res) {
    while ($msg = openssl_error_string()) {
        echo "ERREUR OPENSSL: " . $msg . "\n";
    }
    die("ERREUR : Échec lors de la création de la clé OpenSSL.\n");
}

echo "Exportation de la clé privée chiffrée avec la passphrase...\n";
if (!openssl_pkey_export($res, $privateKey, $passphrase, $config)) {
    while ($msg = openssl_error_string()) {
        echo "ERREUR EXPORT: " . $msg . "\n";
    }
    die("ERREUR : Échec de l'exportation de la clé privée.\n");
}

echo "Génération de la clé publique...\n";
$details = openssl_pkey_get_details($res);
if (!$details || !isset($details["key"])) {
    die("ERREUR : Impossible de récupérer les détails de la clé publique.\n");
}
$publicKey = $details["key"];

$jwtDir = __DIR__ . '/../config/jwt';
if (!is_dir($jwtDir)) {
    mkdir($jwtDir, 0777, true);
}

file_put_contents($jwtDir . '/private.pem', $privateKey);
file_put_contents($jwtDir . '/public.pem', $publicKey);

echo "Clés .pem sauvegardées avec succès dans le dossier config/jwt/\n";

// Nettoyage récursif du cache
echo "Nettoyage du cache de Symfony...\n";
function deleteDir($dirPath) {
    if (!is_dir($dirPath)) {
        return;
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            deleteDir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}

deleteDir(__DIR__ . '/../var/cache');
echo "Cache de Symfony vidé !\n";

// Auto-destruction pour la sécurité
echo "Auto-destruction du script de correction...\n";
unlink(__FILE__);

echo "--- SUCCÈS TOTAL : Les clés sont régénérées, chiffrées et le cache est nettoyé ! ---\n";
