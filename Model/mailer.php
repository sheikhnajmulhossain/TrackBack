<?php
// Model/mailer.php

require_once __DIR__ . '/mail_config.php';

// ✅ Correct absolute paths (no relative guessing)
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendMailSMTP(string $toEmail, string $toName, string $subject, string $html): bool
{
  try {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_SECURE; // 'ssl' for 465, 'tls' for 587
    $mail->Port       = SMTP_PORT;

    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);

    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress($toEmail, $toName);

    $mail->Subject = $subject;
    $mail->Body    = $html;

    $mail->send();
    return true;
  } catch (Exception $e) {
    error_log("MAIL ERROR: " . $e->getMessage());
    return false;
  }
}
