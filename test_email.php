<?php
require_once 'config/mailer.php';

echo "<h1>Test d'envoi d'email</h1>";

try {
    $mailer = new MailerService();

    $testData = [
        'name' => 'Test Local',
        'email' => 'test@example.com',
        'phone' => '0123456789',
        'subject' => 'Email de test depuis local',
        'message' => 'Si tu reçois cet email, PHPMailer fonctionne !'
    ];

    if ($mailer->sendContactForm($testData)) {
        echo "<p style='color: green; font-size: 20px;'>✅ Email envoyé avec succès !</p>";
        echo "<p>Vérifie ta boîte email : " . getenv('MAIL_TO_ADDRESS') . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Erreur d'envoi</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur : " . $e->getMessage() . "</p>";
}
?>
```

Puis lance ton serveur local et va sur :
```
http://localhost/test_email.php