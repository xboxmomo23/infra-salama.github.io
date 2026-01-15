<?php

/**
 * Simple .env file loader
 * Charge les variables d'environnement depuis le fichier .env
 */

function loadEnv($path = __DIR__ . '/../.env')
{
    if (!file_exists($path)) {
        throw new Exception("Fichier .env introuvable. Copiez .env.example en .env et configurez-le.");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Ignorer les commentaires et lignes vides
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parser les lignes KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Retirer les guillemets si présents
            $value = trim($value, '"\'');

            // Définir la variable d'environnement
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
    }
}

/**
 * Récupère une variable d'environnement avec valeur par défaut
 */
function env($key, $default = null)
{
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    // Convertir les booléens
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'null':
        case '(null)':
            return null;
    }

    return $value;
}

// Charger automatiquement le .env
try {
    loadEnv();
} catch (Exception $e) {
    // En production, logger l'erreur
    error_log($e->getMessage());
    die("Erreur de configuration. Contactez l'administrateur.");
}
