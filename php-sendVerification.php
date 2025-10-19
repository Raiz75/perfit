<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
include 'php-config.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? null;
if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Email not provided.']);
    exit;
}
$verificationCode = rand(100000, 999999);
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->setFrom(SMTP_USER, 'PERFIT Support');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'PERFIT Account Verification Code';
    $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;'>
        <div style='background: linear-gradient(135deg, rgb(128, 65, 128) 0%, rgb(255, 223, 255) 100%); padding: 40px 20px; text-align: center; border-radius: 10px 10px 0 0;'>
            <h1 style='color: white; margin: 0; font-size: 28px;'>🎉 Welcome to PERFIT!</h1>
        </div>
        <div style='background-color: white; padding: 40px 30px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
            <p style='font-size: 16px; color: #333; line-height: 1.6;'>Hello there,</p>
            <p style='font-size: 16px; color: #333; line-height: 1.6;'>
                Thank you for joining PERFIT! We're excited to have you on board. To complete your registration, please verify your account using the code below:
            </p>
            <div style='background-color: #f8f4ff; border-left: 4px solid rgb(128, 65, 128); padding: 20px; margin: 30px 0; border-radius: 5px;'>
                <p style='margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;'>Your Verification Code</p>
                <h2 style='color: rgb(128, 65, 128); margin: 0; font-size: 32px; letter-spacing: 2px; font-family: monospace;'>$verificationCode</h2>
            </div>
            <div style='background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 30px 0; border-radius: 5px;'>
                <p style='margin: 0; color: #856404; font-size: 14px;'>
                    <strong>⚠️ Didn't sign up?</strong><br>
                    If you didn't create a PERFIT account, please ignore this email and the account will not be activated.
                </p>
            </div>
        </div>
        <div style='text-align: center; padding: 20px; color: #999; font-size: 12px;'>
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
    ";
    $mail->send();
    // Return the code so JS can check it
    echo json_encode(['success' => true, 'code' => $verificationCode, 'message' => 'Verification code sent.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => "Failed to send email: {$mail->ErrorInfo}"]);
}
?>