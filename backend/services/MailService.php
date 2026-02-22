
<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require_once __DIR__ . '/../../vendor/autoload.php';

class MailService {
    private $mail;

    public function __construct() {
        $this->mail = new PHPMailer(true);

        $this->mail->isSMTP();
        $this->mail->Host       = 'smtp.gmail.com';
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = '1qcuassetmgm@gmail.com';  // Don't mind this, we'll use env in the future. Maybe
        $this->mail->Password   = 'vtmw dnnc nfxr gsek';     
        $this->mail->SMTPSecure = 'tls';
        $this->mail->Port       = 587;
        $this->mail->setFrom('1qcuassetmgm@gmail.com', 'QCU Asset Management');
        $this->mail->isHTML(true);
    }

    public function sendOtpEmail($email, $otp) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($email);

            $this->mail->Subject = 'Your OTP for QCU Asset Management';
            $this->mail->Body    = "
                <h3>OTP Verification</h3>
                <p>Your OTP code is: <strong>$otp</strong></p>
                <p>This OTP is valid for 10 minutes.</p>
            ";

            return $this->mail->send();

        } catch (PHPMailerException $e) {
            error_log("Mail error: " . $e->getMessage());
            return false;
        }
    }
}