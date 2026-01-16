<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Champs requis
$required = ['firstName', 'lastName', 'email', 'phone', 'position', 'experience'];
$errors = [];

foreach ($required as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        $errors[] = "Le champ '$field' est requis";
    }
}

// Validation email
if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "L'adresse email n'est pas valide";
}

// Validation consentement
if (!isset($_POST['privacyConsent']) || $_POST['privacyConsent'] !== 'on') {
    $errors[] = "Vous devez accepter la politique de confidentialité";
}

// Validation fichier CV
if (!isset($_FILES['resume']) || $_FILES['resume']['error'] === UPLOAD_ERR_NO_FILE) {
    $errors[] = "Le CV est obligatoire";
} elseif ($_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = "Erreur lors de l'upload du CV";
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Validation du type et de la taille du fichier CV
$allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$maxSize = 5 * 1024 * 1024; // 5MB

$fileType = $_FILES['resume']['type'];
$fileSize = $_FILES['resume']['size'];

if (!in_array($fileType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Format de CV non autorisé. Utilisez PDF, DOC ou DOCX']);
    exit;
}

if ($fileSize > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Le CV ne doit pas dépasser 5MB']);
    exit;
}

// Créer le dossier uploads si n'existe pas
$uploadDir = __DIR__ . '/../uploads/cv/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Générer un nom unique pour le fichier
$extension = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
$fileName = uniqid('cv_' . time() . '_') . '.' . $extension;
$uploadPath = $uploadDir . $fileName;

// Déplacer le fichier uploadé
if (!move_uploaded_file($_FILES['resume']['tmp_name'], $uploadPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde du CV']);
    exit;
}

// Préparer les données
$data = [
    'firstName' => htmlspecialchars(trim($_POST['firstName'])),
    'lastName' => htmlspecialchars(trim($_POST['lastName'])),
    'email' => filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL),
    'phone' => htmlspecialchars(trim($_POST['phone'])),
    'position' => htmlspecialchars(trim($_POST['position'])),
    'experience' => htmlspecialchars(trim($_POST['experience'])),
    'coverLetter' => isset($_POST['coverLetter']) ? htmlspecialchars(trim($_POST['coverLetter'])) : '',
    'cvFileName' => $_FILES['resume']['name'],
    'cvFilePath' => $uploadPath
];

// Envoyer l'email
try {
    $mailer = new MailerService();
    $sent = $mailer->sendRecruitmentForm($data);

    if ($sent) {
        echo json_encode([
            'success' => true,
            'message' => 'Votre candidature a été envoyée avec succès. Nous examinerons votre profil et vous contacterons si votre candidature correspond à nos besoins.'
        ]);
    } else {
        // Supprimer le fichier si l'envoi échoue
        if (file_exists($uploadPath)) {
            unlink($uploadPath);
        }

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Une erreur est survenue lors de l\'envoi. Veuillez réessayer.'
        ]);
    }
} catch (Exception $e) {
    // Supprimer le fichier en cas d'erreur
    if (file_exists($uploadPath)) {
        unlink($uploadPath);
    }

    http_response_code(500);
    error_log("Erreur API recrutement: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur. Veuillez contacter l\'administrateur.'
    ]);
}
