<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require_once __DIR__ . '/vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = '1qcuassetmgm@gmail.com';
    $mail->Password = 'vtmw dnnc nfxr gsek';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('1qcuassetmgm@gmail.com', 'Test');
    $mail->addAddress('sample@gmail.com'); // Replace this with your real gmail account

    $mail->isHTML(true);
    $mail->Subject = 'Test PHPMailer';
    $mail->Body    = '<h1>This is a test</h1>';

    $mail->send();
    echo "Email sent successfully!";
} catch (PHPMailerException $e) {
    echo "Mail error: " . $e->getMessage();
}

// This file is to test if email sending works
// To run, type in the browser: localhost/1QCUPROJECT/test_email.php