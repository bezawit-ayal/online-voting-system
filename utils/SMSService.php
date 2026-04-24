<?php
// utils/SMSService.php - SMS service for notifications
class SMSService {
    private $config;

    public function __construct() {
        $this->config = require __DIR__ . '/../config/config.php';
    }

    public function sendOTP($phoneNumber, $otp) {
        $message = "VoteSecure Pro - Your verification code is: $otp. Valid for 5 minutes.";

        return $this->sendSMS($phoneNumber, $message);
    }

    public function sendVoteConfirmation($phoneNumber, $electionTitle, $candidateName) {
        $message = "VoteSecure Pro - Your vote for $candidateName in $electionTitle has been recorded successfully.";

        return $this->sendSMS($phoneNumber, $message);
    }

    public function sendElectionReminder($phoneNumber, $electionTitle, $startTime) {
        $message = "VoteSecure Pro - Reminder: Voting for $electionTitle starts at $startTime. Don't forget to cast your vote!";

        return $this->sendSMS($phoneNumber, $message);
    }

    public function sendElectionResults($phoneNumber, $electionTitle, $winnerName) {
        $message = "VoteSecure Pro - Election results for $electionTitle are now available. Winner: $winnerName. Check your dashboard for details.";

        return $this->sendSMS($phoneNumber, $message);
    }

    private function sendSMS($to, $message) {
        $provider = $this->config['sms']['provider'];

        switch ($provider) {
            case 'twilio':
                return $this->sendViaTwilio($to, $message);
            case 'aws_sns':
                return $this->sendViaAWS($to, $message);
            default:
                // For development, just log the SMS
                if ($this->config['app']['debug']) {
                    $this->logSMS($to, $message);
                    return true;
                }
                return false;
        }
    }

    private function sendViaTwilio($to, $message) {
        // Twilio SMS implementation
        $sid = $this->config['sms']['twilio_sid'];
        $token = $this->config['sms']['twilio_token'];
        $from = $this->config['sms']['twilio_from'];

        $url = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";

        $data = [
            'From' => $from,
            'To' => $to,
            'Body' => $message
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, "$sid:$token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        return $httpCode === 201 && isset($result['sid']);
    }

    private function sendViaAWS($to, $message) {
        // AWS SNS implementation would go here
        // This is a placeholder for AWS SNS integration
        $this->logSMS($to, $message);
        return true;
    }

    private function logSMS($to, $message) {
        $logFile = __DIR__ . '/../logs/sms.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] SMS to: $to\nMessage: $message\n\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
?>