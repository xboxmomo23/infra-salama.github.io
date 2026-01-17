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

// Validation des champs requis
$required = ['name', 'email', 'subject', 'message'];
$errors = [];

foreach ($required as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        $errors[] = "Le champ '$field' est requis";
    }
}

// Validation de l'email
if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "L'adresse email n'est pas valide";
}

// Vérification RGPD
if (!isset($_POST['privacy']) || $_POST['privacy'] !== 'on') {
    $errors[] = "Vous devez accepter la politique de confidentialité";
}

if (!empty($errors)) {
    jsonResponse(400, ['success' => false, 'errors' => $errors]);
}

// Nettoyer les données
$data = [
    'name' => htmlspecialchars(trim($_POST['name'])),
    'email' => filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL),
    'phone' => isset($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : '',
    'subject' => htmlspecialchars(trim($_POST['subject'])),
    'message' => htmlspecialchars(trim($_POST['message']))
];

// Envoyer l'email
try {
    $mailer = new MailerService();
    $sent = $mailer->sendContactForm($data);

    if ($sent) {
        jsonResponse(200, [
            'success' => true,
            'message' => 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.'
        ]);
    }

    jsonResponse(500, [
        'success' => false,
        'message' => 'Une erreur est survenue lors de l\'envoi. Veuillez réessayer.'
    ]);
} catch (Exception $e) {
    error_log("Erreur API contact: " . $e->getMessage());
    jsonResponse(500, [
        'success' => false,
        'message' => 'Erreur serveur. Veuillez contacter l\'administrateur.'
    ]);
}
