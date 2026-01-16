<?php
require_once 'config/mailer.php';

echo "<h1>Test d'envoi avec IONOS</h1>";

try {
    $mailer = new MailerService();

    $testData = [
        'name' => 'Test IONOS',
        'email' => 'test@example.com',
        'phone' => '0123456789',
        'subject' => 'Test email depuis contact@infrasalama.com',
        'message' => 'Si tu reçois cet email, la config IONOS fonctionne !'
    ];

    if ($mailer->sendContactForm($testData)) {
        echo "<p style='color: green; font-size: 20px;'>✅ Email envoyé avec succès !</p>";
        echo "<p>Vérifie ta boîte : " . getenv('MAIL_TO_ADDRESS') . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Erreur d'envoi</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur : " . $e->getMessage() . "</p>";
}
