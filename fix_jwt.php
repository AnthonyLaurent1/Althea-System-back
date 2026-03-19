<?php
// On essaie de trouver le fichier de conf OpenSSL de ton installation PHP
$opensslConfig = "C:\\Program Files\\php-8.4.14\\extras\\ssl\\openssl.cnf";

$config = [
    "digest_alg" => "sha512",
    "private_key_bits" => 4096,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
];

// Si le fichier existe, on l'ajoute à la config
if (file_exists($opensslConfig)) {
    $config["config"] = $opensslConfig;
}

echo "Tentative de génération des clés...\n";

$res = openssl_pkey_new($config);

if (!$res) {
    // Si ça échoue encore, on affiche l'erreur OpenSSL exacte
    while ($msg = openssl_error_string()) echo "ERREUR OPENSSL: " . $msg . "\n";
    exit("Impossible de créer la ressource OpenSSL. Vérifie que l'extension openssl est active dans ton php.ini.\n");
}

openssl_pkey_export($res, $privateKey, null, $config);
$publicKey = openssl_pkey_get_details($res)["key"];

if (!is_dir('config/jwt')) {
    mkdir('config/jwt', 0777, true);
}

file_put_contents('config/jwt/private.pem', $privateKey);
file_put_contents('config/jwt/public.pem', $publicKey);

echo "--- SUCCÈS : Les fichiers .pem sont créés ! ---\n";
