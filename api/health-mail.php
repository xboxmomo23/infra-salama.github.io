<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/mailer.php';

function jsonResponse($status, $payload)
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

$token = $_GET['token'] ?? $_POST['token'] ?? null;
$expected = env('HEALTH_MAIL_TOKEN');

if (!$expected || $token !== $expected) {
    jsonResponse(401, ['success' => false, 'message' => 'Accès non autorisé']);
}

try {
    $mailer = new MailerService();
    $sent = $mailer->sendHealthCheck($_GET['to'] ?? $_POST['to'] ?? null);

    if ($sent) {
        jsonResponse(200, ['success' => true, 'message' => 'Email test envoyé']);
    }

    jsonResponse(500, ['success' => false, 'message' => 'Échec de l\'envoi test']);
} catch (Exception $e) {
    error_log("Erreur health-mail: " . $e->getMessage());
    jsonResponse(500, ['success' => false, 'message' => 'Erreur serveur']);
}
