<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    require 'phpmailer/src/Exception.php';
    require 'phpmailer/src/PHPMailer.php';
    require 'phpmailer/src/SMTP.php';
    include 'php-dbCon.php';
    include 'php-config.php';
    header('Content-Type: application/json');
    // Get email from JSON input
    $data = json_decode(file_get_contents("php://input"), true);
    $email = $data['email'] ?? null;
    if (!$email) {
        echo json_encode(['success' => false, 'message' => 'Email not provided.']);
        exit;
    }
    // Check if email exists in DB
    $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Email not found.']);
        exit;
    }
    // Generate temporary password
    $tempPass = rand(100000, 999999);
    $hashedTemp = password_hash($tempPass, PASSWORD_DEFAULT);
    // Update password in DB
    $update = $conn->prepare("UPDATE admin SET password = ? WHERE email = ?");
    $update->bind_param("ss", $hashedTemp, $email);
    $update->execute();
    // Send temporary password via email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom(SMTP_USER, 'PERFIT Support');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = "PERFIT Account Temporary Password";
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;'>
                <div style='background: linear-gradient(135deg, rgb(128, 65, 128) 0%, rgb(255, 223, 255) 100%); padding: 40px 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1 style='color: white; margin: 0; font-size: 28px;'>🔐 Password Reset Request</h1>
                </div>
                <div style='background-color: white; padding: 40px 30px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                    <p style='font-size: 16px; color: #333; line-height: 1.6;'>Hello there,</p>
                    <p style='font-size: 16px; color: #333; line-height: 1.6;'>
                        We received a request to reset your password. Your temporary password is ready and waiting for you below:
                    </p>
                    <div style='background-color: #f8f4ff; border-left: 4px solid rgb(128, 65, 128); padding: 20px; margin: 30px 0; border-radius: 5px;'>
                        <p style='margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;'>Your Temporary Password</p>
                        <h2 style='color: rgb(128, 65, 128); margin: 0; font-size: 32px; letter-spacing: 2px; font-family: monospace;'>$tempPass</h2>
                    </div>
                    <div style='background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 30px 0; border-radius: 5px;'>
                        <p style='margin: 0; color: #856404; font-size: 14px;'>
                            <strong>⚠️ Didn't request this?</strong><br>
                            If you didn't ask for a password reset, please ignore this email.
                        </p>
                    </div>
                </div>
                <div style='text-align: center; padding: 20px; color: #999; font-size: 12px;'>
                    <p>This is an automated message, please do not reply to this email.</p>
                </div>
            </div>
        ";
        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Temporary password sent to your email.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => "Failed to send email: {$mail->ErrorInfo}"]);
    }
    $conn->close();
?>
