<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ✅ Correct paths (since PHPMailer files are in htdocs/)
require_once __DIR__ . '/../Exception.php';
require_once __DIR__ . '/../PHPMailer.php';
require_once __DIR__ . '/../SMTP.php';

// ---------------- Existing OTP function ----------------
function sendOTP($email, $otp)
{
    $subject = "Your OTP Verification Code";
    $message = "
        <html>
        <body>
            <h2 style='color:#007bff;'>EduTech OTP Verification</h2>
            <p>Dear user,</p>
            <p>Your OTP code is: <strong style='font-size:18px;'>$otp</strong></p>
            <p>This OTP is valid for 10 minutes.</p>
            <br>
            <p>Regards,<br>EduTech Team</p>
        </body>
        </html>
    ";
    return sendEmail($email, $subject, $message);
}

// ---------------- Universal Email Function ----------------
function sendEmail($to, $subject, $htmlMessage)
{
    $mail = new PHPMailer(true);

    try {
        // ✅ SMTP Settings (Gmail Example)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'edutechlmsindia@gmail.com';
        $mail->Password = 'liyi gldu algd cwpo';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Sender Info
        $mail->setFrom('edutechlmsindia@gmail.com', 'EduTech');
        $mail->addAddress($to);

        // Email Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlMessage;

        // Send
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
