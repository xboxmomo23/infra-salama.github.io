<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/env.php';

/**
 * Service d'envoi d'emails centralisé
 * Utilise PHPMailer avec configuration depuis .env
 */
class MailerService
{
    private $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    /**
     * Configure PHPMailer avec les paramètres .env
     */
    private function configure()
    {
        try {
            // Configuration serveur
            $this->mailer->isSMTP();
            $this->mailer->Host       = env('MAIL_HOST', 'smtp.gmail.com');
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = env('MAIL_USERNAME');
            $this->mailer->Password   = env('MAIL_PASSWORD');
            $this->mailer->SMTPSecure = env('MAIL_ENCRYPTION', PHPMailer::ENCRYPTION_STARTTLS);
            $this->mailer->Port       = env('MAIL_PORT', 587);
            $this->mailer->CharSet    = 'UTF-8';

            // Expéditeur par défaut
            $this->mailer->setFrom(
                env('MAIL_FROM_ADDRESS'),
                env('MAIL_FROM_NAME', 'Infra Salama')
            );

            // Mode debug (désactivé en production)
            $this->mailer->SMTPDebug = env('APP_ENV') === 'local' ? 2 : 0;
        } catch (Exception $e) {
            error_log("Erreur configuration mailer: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envoie un email de formulaire de contact
     */
    public function sendContactForm($data)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress(env('MAIL_TO_ADDRESS'));

            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Nouveau message depuis le formulaire de contact';

            $this->mailer->Body = $this->getContactEmailTemplate($data);
            $this->mailer->AltBody = $this->getContactEmailPlainText($data);

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erreur envoi email contact: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie un email de demande de devis
     */
    public function sendDevisForm($data)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress(env('MAIL_TO_ADDRESS'));

            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Nouvelle demande de devis - ' . ($data['establishmentName'] ?? 'N/A');

            $this->mailer->Body = $this->getDevisEmailTemplate($data);
            $this->mailer->AltBody = $this->getDevisEmailPlainText($data);

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erreur envoi email devis: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie un email de demande de démo EduPilot
     */
    public function sendDemoForm($data)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress(env('MAIL_TO_ADDRESS'));

            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Demande de démo EduPilot - ' . ($data['nomEtablissement'] ?? 'N/A');

            $this->mailer->Body = $this->getDemoEmailTemplate($data);
            $this->mailer->AltBody = $this->getDemoEmailPlainText($data);

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erreur envoi email démo: " . $e->getMessage());
            return false;
        }
    }

    // Templates HTML pour chaque type d'email

    private function getContactEmailTemplate($data)
    {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
                .content { background: #f8f9fa; padding: 20px; }
                .field { margin-bottom: 15px; }
                .label { font-weight: bold; color: #667eea; }
                .value { margin-top: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Nouveau message de contact</h2>
                </div>
                <div class='content'>
                    <div class='field'>
                        <div class='label'>Nom:</div>
                        <div class='value'>" . htmlspecialchars($data['name'] ?? 'N/A') . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Email:</div>
                        <div class='value'>" . htmlspecialchars($data['email'] ?? 'N/A') . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Téléphone:</div>
                        <div class='value'>" . htmlspecialchars($data['phone'] ?? 'N/A') . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Sujet:</div>
                        <div class='value'>" . htmlspecialchars($data['subject'] ?? 'N/A') . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Message:</div>
                        <div class='value'>" . nl2br(htmlspecialchars($data['message'] ?? 'N/A')) . "</div>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }

    private function getContactEmailPlainText($data)
    {
        return "NOUVEAU MESSAGE DE CONTACT\n\n" .
            "Nom: " . ($data['name'] ?? 'N/A') . "\n" .
            "Email: " . ($data['email'] ?? 'N/A') . "\n" .
            "Téléphone: " . ($data['phone'] ?? 'N/A') . "\n" .
            "Sujet: " . ($data['subject'] ?? 'N/A') . "\n\n" .
            "Message:\n" . ($data['message'] ?? 'N/A');
    }

    private function getDevisEmailTemplate($data)
    {
        $services = isset($data['services']) ? implode(', ', $data['services']) : 'Aucun';

        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
                .section { background: #f8f9fa; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
                .field { margin-bottom: 15px; }
                .label { font-weight: bold; color: #667eea; }
                .value { margin-top: 5px; }
                h3 { color: #667eea; margin-top: 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Nouvelle demande de devis</h2>
                </div>
                
                <div class='section'>
                    <h3>Contact</h3>
                    <div class='field'>
                        <div class='label'>Nom:</div>
                        <div class='value'>" . htmlspecialchars(($data['firstName'] ?? '') . ' ' . ($data['lastName'] ?? '')) . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Email:</div>
                        <div class='value'>" . htmlspecialchars($data['email'] ?? 'N/A') . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Téléphone:</div>
                        <div class='value'>" . htmlspecialchars($data['phone'] ?? 'N/A') . "</div>
                    </div>
                </div>

                <div class='section'>
                    <h3>Établissement</h3>
                    <div class='field'>
                        <div class='label'>Nom:</div>
                        <div class='value'>" . htmlspecialchars($data['establishmentName'] ?? 'N/A') . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Type:</div>
                        <div class='value'>" . htmlspecialchars($data['establishmentType'] ?? 'N/A') . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Adresse:</div>
                        <div class='value'>" . htmlspecialchars($data['address'] ?? 'N/A') . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Ville:</div>
                        <div class='value'>" . htmlspecialchars($data['city'] ?? 'N/A') . " - " . htmlspecialchars($data['postalCode'] ?? 'N/A') . "</div>
                    </div>
                </div>

                <div class='section'>
                    <h3>Projet</h3>
                    <div class='field'>
                        <div class='label'>Services requis:</div>
                        <div class='value'>" . htmlspecialchars($services) . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Taille:</div>
                        <div class='value'>" . htmlspecialchars($data['establishmentSize'] ?? 'N/A') . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Budget:</div>
                        <div class='value'>" . htmlspecialchars($data['budget'] ?? 'Non défini') . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Délai:</div>
                        <div class='value'>" . htmlspecialchars($data['timeline'] ?? 'N/A') . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Description:</div>
                        <div class='value'>" . nl2br(htmlspecialchars($data['projectDescription'] ?? 'N/A')) . "</div>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }

    private function getDevisEmailPlainText($data)
    {
        $services = isset($data['services']) ? implode(', ', $data['services']) : 'Aucun';

        return "NOUVELLE DEMANDE DE DEVIS\n\n" .
            "=== CONTACT ===\n" .
            "Nom: " . ($data['firstName'] ?? '') . ' ' . ($data['lastName'] ?? '') . "\n" .
            "Email: " . ($data['email'] ?? 'N/A') . "\n" .
            "Téléphone: " . ($data['phone'] ?? 'N/A') . "\n\n" .
            "=== ÉTABLISSEMENT ===\n" .
            "Nom: " . ($data['establishmentName'] ?? 'N/A') . "\n" .
            "Type: " . ($data['establishmentType'] ?? 'N/A') . "\n" .
            "Adresse: " . ($data['address'] ?? 'N/A') . "\n" .
            "Ville: " . ($data['city'] ?? 'N/A') . " - " . ($data['postalCode'] ?? 'N/A') . "\n\n" .
            "=== PROJET ===\n" .
            "Services: " . $services . "\n" .
            "Taille: " . ($data['establishmentSize'] ?? 'N/A') . "\n" .
            "Budget: " . ($data['budget'] ?? 'Non défini') . "\n" .
            "Délai: " . ($data['timeline'] ?? 'N/A') . "\n\n" .
            "Description:\n" . ($data['projectDescription'] ?? 'N/A');
    }

    private function getDemoEmailTemplate($data)
    {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
                .section { background: #f8f9fa; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
                .field { margin-bottom: 15px; }
                .label { font-weight: bold; color: #667eea; }
                .value { margin-top: 5px; }
                h3 { color: #667eea; margin-top: 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Demande de démonstration EduPilot</h2>
                </div>
                
                <div class='section'>
                    <h3>Établissement</h3>
                    <div class='field'>
                        <div class='label'>Nom:</div>
                        <div class='value'>" . htmlspecialchars($data['nomEtablissement'] ?? 'N/A') . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Type:</div>
                        <div class='value'>" . htmlspecialchars($data['typeEtablissement'] ?? 'N/A') . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Localisation:</div>
                        <div class='value'>" . htmlspecialchars($data['ville'] ?? 'N/A') . " - Wilaya " . htmlspecialchars($data['wilaya'] ?? 'N/A') . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Nombre d'élèves:</div>
                        <div class='value'>" . htmlspecialchars($data['nombreEleves'] ?? 'N/A') . "</div>
                    </div>
                </div>

                <div class='section'>
                    <h3>Contact</h3>
                    <div class='field'>
                        <div class='label'>Nom:</div>
                        <div class='value'>" . htmlspecialchars($data['nomContact'] ?? 'N/A') . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Fonction:</div>
                        <div class='value'>" . htmlspecialchars($data['fonction'] ?? 'N/A') . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Email:</div>
                        <div class='value'>" . htmlspecialchars($data['email'] ?? 'N/A') . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Téléphone:</div>
                        <div class='value'>" . htmlspecialchars($data['telephone'] ?? 'N/A') . "</div>
                    </div>
                </div>

                " . (isset($data['message']) && !empty($data['message']) ? "
                <div class='section'>
                    <h3>Message</h3>
                    <div class='value'>" . nl2br(htmlspecialchars($data['message'])) . "</div>
                </div>" : "") . "
            </div>
        </body>
        </html>";
    }

    private function getDemoEmailPlainText($data)
    {
        return "DEMANDE DE DÉMONSTRATION EDUPILOT\n\n" .
            "=== ÉTABLISSEMENT ===\n" .
            "Nom: " . ($data['nomEtablissement'] ?? 'N/A') . "\n" .
            "Type: " . ($data['typeEtablissement'] ?? 'N/A') . "\n" .
            "Ville: " . ($data['ville'] ?? 'N/A') . " - Wilaya " . ($data['wilaya'] ?? 'N/A') . "\n" .
            "Nombre d'élèves: " . ($data['nombreEleves'] ?? 'N/A') . "\n\n" .
            "=== CONTACT ===\n" .
            "Nom: " . ($data['nomContact'] ?? 'N/A') . "\n" .
            "Fonction: " . ($data['fonction'] ?? 'N/A') . "\n" .
            "Email: " . ($data['email'] ?? 'N/A') . "\n" .
            "Téléphone: " . ($data['telephone'] ?? 'N/A') . "\n\n" .
            (isset($data['message']) && !empty($data['message']) ? "=== MESSAGE ===\n" . $data['message'] : "");
    }
}
