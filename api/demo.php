<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/mailer.php';

function jsonResponse($status, $payload)
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['success' => false, 'message' => 'Méthode non autorisée']);
}

// Champs requis
$required = [
    'nomEtablissement',
    'typeEtablissement',
    'ville',
    'wilaya',
    'nombreEleves',
    'nomContact',
    'fonction',
    'email',
    'telephone'
];

$errors = [];

foreach ($required as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        $errors[] = "Le champ '$field' est requis";
    }
}

if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "L'adresse email n'est pas valide";
}

if (!isset($_POST['acceptDemo']) || $_POST['acceptDemo'] !== 'on') {
    $errors[] = "Vous devez accepter d'être contacté pour la démonstration";
}

if (!isset($_POST['rgpd']) || $_POST['rgpd'] !== 'on') {
    $errors[] = "Vous devez accepter la politique de confidentialité";
}

if (!empty($errors)) {
    jsonResponse(400, ['success' => false, 'errors' => $errors]);
}

// Nettoyer les données
$data = [
    'nomEtablissement' => htmlspecialchars(trim($_POST['nomEtablissement'])),
    'typeEtablissement' => htmlspecialchars(trim($_POST['typeEtablissement'])),
    'ville' => htmlspecialchars(trim($_POST['ville'])),
    'wilaya' => htmlspecialchars(trim($_POST['wilaya'])),
    'nombreEleves' => htmlspecialchars(trim($_POST['nombreEleves'])),
    'nomContact' => htmlspecialchars(trim($_POST['nomContact'])),
    'fonction' => htmlspecialchars(trim($_POST['fonction'])),
    'email' => filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL),
    'telephone' => htmlspecialchars(trim($_POST['telephone'])),
    'message' => isset($_POST['message']) ? htmlspecialchars(trim($_POST['message'])) : ''
];

try {
    $mailer = new MailerService();
    $sent = $mailer->sendDemoForm($data);

    if ($sent) {
        jsonResponse(200, [
            'success' => true,
            'message' => 'Votre demande de démonstration a été envoyée avec succès. Nous vous contacterons sous 48 heures.'
        ]);
    }

    jsonResponse(500, [
        'success' => false,
        'message' => 'Une erreur est survenue lors de l\'envoi. Veuillez réessayer.'
    ]);
} catch (Exception $e) {
    error_log("Erreur API demo: " . $e->getMessage());
    jsonResponse(500, [
        'success' => false,
        'message' => 'Erreur serveur. Veuillez contacter l\'administrateur.'
    ]);
}
