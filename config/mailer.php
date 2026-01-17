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
    private $logFile;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->logFile = __DIR__ . '/../logs/mailer.log';
        $this->ensureLogPath();
        $this->configure();
    }

    /**
     * Configure PHPMailer avec les paramètres .env
     */
    private function configure()
    {
        try {
            $host = env('SMTP_HOST', env('MAIL_HOST', 'smtp.gmail.com'));
            $port = (int) env('SMTP_PORT', env('MAIL_PORT', 587));
            $secure = strtolower((string) env('SMTP_SECURE', env('MAIL_ENCRYPTION', 'tls')));
            $username = env('SMTP_USER', env('MAIL_USERNAME'));
            $password = env('SMTP_PASS', env('MAIL_PASSWORD'));
            $from = env('MAIL_FROM', env('MAIL_FROM_ADDRESS', $username));
            $fromName = env('MAIL_FROM_NAME', 'Infra Salama');

            $this->mailer->isSMTP();
            $this->mailer->Host = $host;
            $this->mailer->Port = $port;
            $this->mailer->SMTPAuth = !empty($username);
            $this->mailer->Username = $username;
            $this->mailer->Password = $password;
            $this->mailer->SMTPSecure = $secure === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->isHTML(true);

            if ($from) {
                $this->mailer->setFrom($from, $fromName);
            }

            // Mode debug (redirigé vers error_log en local uniquement)
            $this->mailer->SMTPDebug = env('APP_ENV') === 'local' ? 2 : 0;
            if ($this->mailer->SMTPDebug > 0) {
                $this->mailer->Debugoutput = 'error_log';
            }
        } catch (Exception $e) {
            $this->log('configuration', 'error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Envoie un email de formulaire de contact
     */
    public function sendContactForm($data)
    {
        try {
            $this->setRecipients(env('CONTACT_TO', env('MAIL_TO_ADDRESS')));
            $this->mailer->Subject = 'Nouveau message depuis le formulaire de contact';

            $this->mailer->Body = $this->getContactEmailTemplate($data);
            $this->mailer->AltBody = $this->getContactEmailPlainText($data);

            $sent = $this->mailer->send();
            $this->log('contact', $sent ? 'success' : 'error');
            return $sent;
        } catch (Exception $e) {
            $this->log('contact', 'error', ['error' => $e->getMessage(), 'mail_error' => $this->mailer->ErrorInfo]);
            return false;
        }
    }

    /**
     * Envoie un email de demande de devis
     */
    public function sendDevisForm($data)
    {
        try {
            $this->setRecipients(env('QUOTE_TO', env('MAIL_TO_ADDRESS')));
            $this->mailer->Subject = 'Nouvelle demande de devis - ' . ($data['establishmentName'] ?? 'N/A');

            $this->mailer->Body = $this->getDevisEmailTemplate($data);
            $this->mailer->AltBody = $this->getDevisEmailPlainText($data);

            $sent = $this->mailer->send();
            $this->log('devis', $sent ? 'success' : 'error');
            return $sent;
        } catch (Exception $e) {
            $this->log('devis', 'error', ['error' => $e->getMessage(), 'mail_error' => $this->mailer->ErrorInfo]);
            return false;
        }
    }

    /**
     * Envoie un email de demande de démo EduPilot
     */
    public function sendDemoForm($data)
    {
        try {
            $this->setRecipients(env('DEMO_TO', env('MAIL_TO_ADDRESS')));
            $this->mailer->Subject = 'Demande de démo EduPilot - ' . ($data['nomEtablissement'] ?? 'N/A');

            $this->mailer->Body = $this->getDemoEmailTemplate($data);
            $this->mailer->AltBody = $this->getDemoEmailPlainText($data);

            $sent = $this->mailer->send();
            $this->log('demo', $sent ? 'success' : 'error');
            return $sent;
        } catch (Exception $e) {
            $this->log('demo', 'error', ['error' => $e->getMessage(), 'mail_error' => $this->mailer->ErrorInfo]);
            return false;
        }
    }

    /**
     * Envoie un email de candidature (recrutement)
     */
    public function sendRecruitmentForm($data)
    {
        try {
            $this->setRecipients(env('RECRUIT_TO', env('MAIL_TO_ADDRESS')));
            $this->mailer->clearAttachments();

            // Attacher le CV
            if (isset($data['cvFilePath']) && file_exists($data['cvFilePath'])) {
                $this->mailer->addAttachment($data['cvFilePath'], $data['cvFileName']);
            }

            $this->mailer->Subject = 'Nouvelle candidature - ' . ($data['position'] ?? 'N/A') . ' - ' . ($data['firstName'] ?? '') . ' ' . ($data['lastName'] ?? '');

            $this->mailer->Body = $this->getRecruitmentEmailTemplate($data);
            $this->mailer->AltBody = $this->getRecruitmentEmailPlainText($data);

            $sent = $this->mailer->send();
            $this->log('recrutement', $sent ? 'success' : 'error');
            return $sent;
        } catch (Exception $e) {
            $this->log('recrutement', 'error', ['error' => $e->getMessage(), 'mail_error' => $this->mailer->ErrorInfo]);
            return false;
        }
    }

    public function sendHealthCheck($recipient = null)
    {
        $to = $recipient ?: env('CONTACT_TO', env('MAIL_TO_ADDRESS'));
        try {
            $this->setRecipients($to);
            $this->mailer->Subject = 'Health check email - Infra Salama';
            $this->mailer->Body = '<p>Test d\'envoi SMTP depuis api/health-mail.php</p>';
            $this->mailer->AltBody = 'Test d\'envoi SMTP depuis api/health-mail.php';

            $sent = $this->mailer->send();
            $this->log('health-mail', $sent ? 'success' : 'error');
            return $sent;
        } catch (Exception $e) {
            $this->log('health-mail', 'error', ['error' => $e->getMessage(), 'mail_error' => $this->mailer->ErrorInfo]);
            return false;
        }
    }

    private function setRecipients($addressList)
    {
        $this->mailer->clearAddresses();
        $addresses = array_filter(array_map('trim', explode(',', (string) $addressList)));
        foreach ($addresses as $address) {
            $this->mailer->addAddress($address);
        }
    }

    private function ensureLogPath()
    {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    private function log($endpoint, $status, array $context = [])
    {
        $toAddresses = array_map(function ($address) {
            return $address[0] ?? '';
        }, $this->mailer->getToAddresses());

        $line = sprintf(
            "[%s] endpoint=%s status=%s to=%s from=%s host=%s user=%s error=%s%s",
            date('Y-m-d H:i:s'),
            $endpoint,
            $status,
            implode(',', array_filter($toAddresses)),
            $this->mailer->From,
            $this->mailer->Host,
            $this->mailer->Username,
            $context['error'] ?? '-',
            PHP_EOL
        );

        if (isset($context['mail_error']) && $context['mail_error']) {
            $line = rtrim($line, PHP_EOL) . ' mail_error=' . $context['mail_error'] . PHP_EOL;
        }

        file_put_contents($this->logFile, $line, FILE_APPEND);
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
                    <h2>Demande de démo EduPilot</h2>
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

    private function getRecruitmentEmailTemplate($data)
    {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #0056b3 0%, #003d82 100%); color: white; padding: 20px; text-align: center; }
                .section { background: #f8f9fa; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
                .field { margin-bottom: 15px; }
                .label { font-weight: bold; color: #0056b3; }
                .value { margin-top: 5px; }
                h3 { color: #0056b3; margin-top: 0; }
                .badge { display: inline-block; background: #0056b3; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.9rem; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🔥 Nouvelle Candidature</h2>
                    <span class='badge'>" . htmlspecialchars($data['position'] ?? 'N/A') . "</span>
                </div>
                
                <div class='section'>
                    <h3>👤 Informations du candidat</h3>
                    <div class='field'>
                        <div class='label'>Nom complet:</div>
                        <div class='value'>" . htmlspecialchars($data['firstName'] ?? '') . " " . htmlspecialchars($data['lastName'] ?? '') . "</div>
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
                    <h3>💼 Informations professionnelles</h3>
                    <div class='field'>
                        <div class='label'>Poste souhaité:</div>
                        <div class='value'>" . htmlspecialchars($data['position'] ?? 'N/A') . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Années d'expérience:</div>
                        <div class='value'>" . htmlspecialchars($data['experience'] ?? 'N/A') . "</div>
                    </div>
                </div>

                " . (isset($data['coverLetter']) && !empty($data['coverLetter']) ? "
                <div class='section'>
                    <h3>📝 Lettre de motivation</h3>
                    <div class='value'>" . nl2br(htmlspecialchars($data['coverLetter'])) . "</div>
                </div>" : "") . "

                <div class='section'>
                    <h3>📎 Pièce jointe</h3>
                    <div class='value'>
                        <strong>CV:</strong> " . htmlspecialchars($data['cvFileName'] ?? 'N/A') . " (voir pièce jointe)
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }

    private function getRecruitmentEmailPlainText($data)
    {
        return "NOUVELLE CANDIDATURE\n\n" .
            "=== CANDIDAT ===\n" .
            "Nom: " . ($data['firstName'] ?? '') . ' ' . ($data['lastName'] ?? '') . "\n" .
            "Email: " . ($data['email'] ?? 'N/A') . "\n" .
            "Téléphone: " . ($data['phone'] ?? 'N/A') . "\n\n" .
            "=== PROFIL ===\n" .
            "Poste souhaité: " . ($data['position'] ?? 'N/A') . "\n" .
            "Expérience: " . ($data['experience'] ?? 'N/A') . "\n\n" .
            (isset($data['coverLetter']) && !empty($data['coverLetter']) ? "=== LETTRE DE MOTIVATION ===\n" . $data['coverLetter'] . "\n\n" : "") .
            "=== CV ===\n" .
            "Fichier: " . ($data['cvFileName'] ?? 'N/A') . " (voir pièce jointe)";
    }
}
