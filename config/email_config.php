<?php
/**
 * Email Configuration for TrashRoute Application
 * 
 * IMPORTANT: For Gmail SMTP to work properly:
 * 1. Enable 2-Factor Authentication on your Gmail account
 * 2. Generate an App Password (not your regular password)
 * 3. Use the App Password in the EMAIL_PASSWORD constant below
 * 
 * To generate Gmail App Password:
 * 1. Go to Google Account settings
 * 2. Security → 2-Step Verification → App passwords
 * 3. Generate a new app password for "Mail"
 * 4. Use that password below (16 characters without spaces)
 */

class EmailConfig {
    // Gmail SMTP Configuration
    const EMAIL_HOST = 'smtp.gmail.com';
    const EMAIL_PORT = 587;
    const EMAIL_USERNAME = 'trashroute.waste@gmail.com';
    const EMAIL_PASSWORD = 'sqivfcuskalupckr'; // App Password (16 characters without spaces)
    const EMAIL_FROM_NAME = 'TrashRoute';
    const EMAIL_ENCRYPTION = 'tls'; // or 'ssl' for port 465
    
    // Debug settings
    const DEBUG_MODE = false; // Set to false in production to prevent JSON interference
    const DEBUG_LEVEL = 0; // 0 = off, 1 = client, 2 = server, 3 = connection, 4 = lowlevel
    
    /**
     * Get PHPMailer configuration array
     */
    public static function getPHPMailerConfig() {
        return [
            'host' => self::EMAIL_HOST,
            'port' => self::EMAIL_PORT,
            'username' => self::EMAIL_USERNAME,
            'password' => self::EMAIL_PASSWORD,
            'encryption' => self::EMAIL_ENCRYPTION,
            'from_name' => self::EMAIL_FROM_NAME,
            'debug_mode' => self::DEBUG_MODE,
            'debug_level' => self::DEBUG_LEVEL
        ];
    }
    
    /**
     * Configure PHPMailer instance with proper settings
     */
    public static function configurePHPMailer($mail) {
        // Server settings
        $mail->isSMTP();
        $mail->Host = self::EMAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = self::EMAIL_USERNAME;
        $mail->Password = self::EMAIL_PASSWORD;
        $mail->SMTPSecure = self::EMAIL_ENCRYPTION;
        $mail->Port = self::EMAIL_PORT;
        
        // Debug settings - only enable for testing, not production
        if (self::DEBUG_MODE) {
            $mail->SMTPDebug = self::DEBUG_LEVEL;
            $mail->Debugoutput = 'error_log'; // Log to error log instead of output
        }
        
        // Additional settings for better reliability
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Timeout settings
        $mail->Timeout = 60;
        $mail->SMTPKeepAlive = true;
        
        return $mail;
    }
}
?>
