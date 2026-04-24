<?php
// utils/EmailService.php - Email service for notifications and verification
class EmailService {
    private $config;

    public function __construct() {
        $this->config = require __DIR__ . '/../config/config.php';
    }

    public function sendVerificationEmail($userEmail, $userName, $verificationToken) {
        $subject = 'Verify Your Email - VoteSecure Pro';
        $verificationUrl = $this->config['app']['url'] . "/verify-email.php?token=$verificationToken";

        $message = "
        <html>
        <head>
            <title>Email Verification</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>VoteSecure Pro</h1>
                    <p>Email Verification</p>
                </div>
                <div class='content'>
                    <h2>Hello $userName,</h2>
                    <p>Thank you for registering with VoteSecure Pro. To complete your registration and start voting, please verify your email address.</p>
                    <p><a href='$verificationUrl' class='button'>Verify Email Address</a></p>
                    <p>If the button doesn't work, copy and paste this link into your browser:</p>
                    <p><a href='$verificationUrl'>$verificationUrl</a></p>
                    <p>This link will expire in 24 hours.</p>
                </div>
                <div class='footer'>
                    <p>If you didn't create an account, please ignore this email.</p>
                    <p>&copy; 2026 VoteSecure Pro. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        return $this->sendEmail($userEmail, $subject, $message);
    }

    public function sendPasswordResetEmail($userEmail, $userName, $resetToken) {
        $subject = 'Password Reset - VoteSecure Pro';
        $resetUrl = $this->config['app']['url'] . "/reset-password.php?token=$resetToken";

        $message = "
        <html>
        <head>
            <title>Password Reset</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>VoteSecure Pro</h1>
                    <p>Password Reset</p>
                </div>
                <div class='content'>
                    <h2>Hello $userName,</h2>
                    <p>You have requested to reset your password. Click the button below to create a new password.</p>
                    <p><a href='$resetUrl' class='button'>Reset Password</a></p>
                    <p>If the button doesn't work, copy and paste this link into your browser:</p>
                    <p><a href='$resetUrl'>$resetUrl</a></p>
                    <div class='warning'>
                        <strong>Security Notice:</strong> This link will expire in 1 hour. If you didn't request this reset, please ignore this email.
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";

        return $this->sendEmail($userEmail, $subject, $message);
    }

    public function sendVoteConfirmationEmail($userEmail, $userName, $electionTitle, $candidateName) {
        $subject = 'Vote Confirmation - VoteSecure Pro';

        $message = "
        <html>
        <head>
            <title>Vote Confirmation</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .success-icon { text-align: center; font-size: 48px; color: #28a745; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Vote Confirmed</h1>
                </div>
                <div class='content'>
                    <div class='success-icon'>✓</div>
                    <h2>Hello $userName,</h2>
                    <p>Your vote has been successfully recorded!</p>
                    <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;'>
                        <h3>Election: $electionTitle</h3>
                        <p><strong>Your Choice:</strong> $candidateName</p>
                        <p><em>Voted on: " . date('F j, Y \a\t g:i A') . "</em></p>
                    </div>
                    <p>You can view the current results and track the election progress on your dashboard.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        return $this->sendEmail($userEmail, $subject, $message);
    }

    public function sendElectionNotification($userEmail, $userName, $electionTitle, $startDate) {
        $subject = 'New Election Available - VoteSecure Pro';

        $message = "
        <html>
        <head>
            <title>New Election</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>New Election Available</h1>
                </div>
                <div class='content'>
                    <h2>Hello $userName,</h2>
                    <p>A new election is now available for voting!</p>
                    <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea;'>
                        <h3>$electionTitle</h3>
                        <p><strong>Starts:</strong> " . date('F j, Y \a\t g:i A', strtotime($startDate)) . "</p>
                    </div>
                    <p>Make sure to cast your vote when voting begins. You can access the election from your dashboard.</p>
                    <p><a href='" . $this->config['app']['url'] . "/dashboard.php' class='button'>Go to Dashboard</a></p>
                </div>
            </div>
        </body>
        </html>
        ";

        return $this->sendEmail($userEmail, $subject, $message);
    }

    private function sendEmail($to, $subject, $message) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->config['email']['from_email'],
            'Reply-To: ' . $this->config['email']['from_email'],
            'X-Mailer: PHP/' . phpversion()
        ];

        // For development, just log the email instead of sending
        if ($this->config['app']['debug']) {
            $this->logEmail($to, $subject, $message);
            return true;
        }

        // In production, use SMTP or mail service
        return mail($to, $subject, $message, implode("\r\n", $headers));
    }

    private function logEmail($to, $subject, $message) {
        $logFile = __DIR__ . '/../logs/email.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] Email to: $to\nSubject: $subject\n---\n$message\n---\n\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
?>