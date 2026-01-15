<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Champs requis
$required = [
    'firstName',
    'lastName',
    'email',
    'phone',
    'establishmentName',
    'establishmentType',
    'address',
    'city',
    'postalCode',
    'establishmentSize',
    'existingInfrastructure',
    'timeline',
    'projectDescription'
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

if (!isset($_POST['privacy']) || $_POST['privacy'] !== 'on') {
    $errors[] = "Vous devez accepter la politique de confidentialité";
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Récupérer les services sélectionnés (checkboxes)
$services = [];
$serviceFields = [
    'networkInstallation' => 'Installation complète de réseaux',
    'infrastructureAudit' => 'Audit d\'infrastructure existante',
    'networkSecurity' => 'Sécurisation de réseau',
    'wifiSolutions' => 'Solutions Wi-Fi',
    'serverManagement' => 'Gestion de serveurs',
    'technicalSupport' => 'Support technique'
];

foreach ($serviceFields as $field => $label) {
    if (isset($_POST[$field]) && $_POST[$field] === 'on') {
        $services[] = $label;
    }
}

// Nettoyer les données
$data = [
    'firstName' => htmlspecialchars(trim($_POST['firstName'])),
    'lastName' => htmlspecialchars(trim($_POST['lastName'])),
    'email' => filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL),
    'phone' => htmlspecialchars(trim($_POST['phone'])),
    'establishmentName' => htmlspecialchars(trim($_POST['establishmentName'])),
    'establishmentType' => htmlspecialchars(trim($_POST['establishmentType'])),
    'address' => htmlspecialchars(trim($_POST['address'])),
    'city' => htmlspecialchars(trim($_POST['city'])),
    'postalCode' => htmlspecialchars(trim($_POST['postalCode'])),
    'services' => $services,
    'establishmentSize' => htmlspecialchars(trim($_POST['establishmentSize'])),
    'existingInfrastructure' => htmlspecialchars(trim($_POST['existingInfrastructure'])),
    'budget' => isset($_POST['budget']) ? htmlspecialchars(trim($_POST['budget'])) : 'Non défini',
    'timeline' => htmlspecialchars(trim($_POST['timeline'])),
    'projectDescription' => htmlspecialchars(trim($_POST['projectDescription'])),
    'hearAboutUs' => isset($_POST['hearAboutUs']) ? htmlspecialchars(trim($_POST['hearAboutUs'])) : '',
    'additionalInfo' => isset($_POST['additionalInfo']) ? htmlspecialchars(trim($_POST['additionalInfo'])) : ''
];

try {
    $mailer = new MailerService();
    $sent = $mailer->sendDevisForm($data);

    if ($sent) {
        echo json_encode([
            'success' => true,
            'message' => 'Votre demande de devis a été envoyée avec succès. Nous vous contacterons sous 3 à 5 jours ouvrables.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Une erreur est survenue lors de l\'envoi. Veuillez réessayer.'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Erreur API devis: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur. Veuillez contacter l\'administrateur.'
    ]);
}
