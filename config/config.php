<?php
// config/config.php - Main application configuration
return [
    'app' => [
        'name' => 'VoteSecure Pro',
        'version' => '2.0.0',
        'url' => 'http://localhost/online-voting-system',
        'timezone' => 'UTC',
        'debug' => true,
        'maintenance' => false,
    ],

    'security' => [
        'session_lifetime' => 3600 * 24 * 7, // 7 days
        'otp_lifetime' => 300, // 5 minutes
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
        'password_min_length' => 8,
        'require_verification' => true,
    ],

    'email' => [
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_username' => 'your-email@gmail.com',
        'smtp_password' => 'your-app-password',
        'from_email' => 'noreply@voting.com',
        'from_name' => 'VoteSecure Pro',
        'encryption' => 'tls',
    ],

    'sms' => [
        'provider' => 'twilio', // twilio, aws_sns, etc.
        'twilio_sid' => 'your-twilio-sid',
        'twilio_token' => 'your-twilio-token',
        'twilio_from' => '+1234567890',
    ],

    'features' => [
        'multi_election' => true,
        'email_verification' => true,
        'sms_notifications' => true,
        'voting_timer' => true,
        'audit_logs' => true,
        'dark_mode' => true,
        'real_time_updates' => true,
        'search_filter' => true,
    ],

    'limits' => [
        'max_candidates_per_election' => 50,
        'voting_time_limit' => 300, // 5 minutes
        'results_refresh_interval' => 30, // seconds
        'max_notifications_per_day' => 10,
    ],

    'api' => [
        'rate_limit' => 100, // requests per minute
        'token_expiry' => 3600, // 1 hour
    ]
];
?>