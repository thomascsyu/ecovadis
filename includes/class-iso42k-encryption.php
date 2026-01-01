<?php
/**
 * ISO 42001 Encryption Class (required for API key storage)
 */

if (!defined('ABSPATH')) {
    exit;
}

class ISO42K_Encryption {
    /**
     * Encrypt data
     */
    public static function encrypt($data) {
        if (empty($data)) return '';
        
        $key = self::get_encryption_key();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    /**
     * Decrypt data
     */
    public static function decrypt($data) {
        if (empty($data)) return '';
        
        $key = self::get_encryption_key();
        $parts = explode('::', base64_decode($data));
        
        if (count($parts) !== 2) return '';
        
        return openssl_decrypt($parts[0], 'aes-256-cbc', $key, 0, $parts[1]);
    }

    /**
     * Get encryption key (WordPress salt)
     */
    private static function get_encryption_key() {
        global $wp_hasher;
        
        if (empty($wp_hasher)) {
            require_once ABSPATH . WPINC . '/class-phpass.php';
            $wp_hasher = new PasswordHash(8, true);
        }
        
        return hash('sha256', AUTH_KEY . SECURE_AUTH_KEY);
    }
}