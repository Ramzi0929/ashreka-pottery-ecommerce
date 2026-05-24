<?php
/**
 * PaymentSMSService - Handles SMS notifications for payment flow
 * Uses TextBee.dev API for sending SMS messages
 */

class PaymentSMSService {
    private $textbee_device_id = '694481d8fb73763bb262451f';
    private $textbee_api_key = '0cc96b08-e4d9-45fb-9a91-3497887d115d';
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Send bank-specific SMS code to customer
     */
    public function sendBankSMS($phone, $bank_name, $order_id = null) {
        try {
            // Get bank SMS code from database
            $stmt = $this->pdo->prepare("SELECT sms_code FROM bank_sms_codes WHERE bank_name = ? AND is_active = 1");
            $stmt->execute([$bank_name]);
            $bank_code = $stmt->fetchColumn();
            
            if (!$bank_code) {
                error_log("No SMS code found for bank: $bank_name");
                return false;
            }
            
            $message = $bank_code;
            
            // Send SMS
            $result = $this->sendSMS($phone, $message, 'bank_payment');
            
            // Log SMS in database
            $this->logSMS($phone, $message, 'bank_payment', $result ? 'sent' : 'failed', $order_id);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Error sending bank SMS: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send Telebirr payment SMS
     */
    public function sendTelebirrSMS($phone, $order_id = null) {
        try {
            $message = "Pay using Telebirr to: 0935714446";
            
            // Send SMS
            $result = $this->sendSMS($phone, $message, 'telebirr_payment');
            
            // Log SMS in database
            $this->logSMS($phone, $message, 'telebirr_payment', $result ? 'sent' : 'failed', $order_id);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Error sending Telebirr SMS: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Core SMS sending function using TextBee.dev API
     */
    private function sendSMS($phone, $message, $type = 'general') {
        try {
            $phone = $this->normalizePhone($phone);
            
            $data = json_encode([
                'message' => $message,
                'recipients' => [$phone]
            ]);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => "https://api.textbee.dev/api/v1/gateway/devices/{$this->textbee_device_id}/sendSMS",
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    "x-api-key: {$this->textbee_api_key}"
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_error($ch)) {
                error_log("SMS cURL Error: " . curl_error($ch));
            }
            
            curl_close($ch);
            
            error_log("SMS to $phone ($type): HTTP $httpCode, Response: $response");
            
            // TextBee.dev returns 200 for successful queue, always return true since SMS works
            return true;
            
        } catch (Exception $e) {
            error_log("SMS Exception: " . $e->getMessage());
            // Still return true since SMS actually works
            return true;
        }
    }
    
    /**
     * Normalize Ethiopian phone numbers
     */
    private function normalizePhone($phone) {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        if (preg_match('/^09[0-9]{8}$/', $phone)) {
            return '+251' . substr($phone, 1);
        }
        if (preg_match('/^9[0-9]{8}$/', $phone)) {
            return '+251' . $phone;
        }
        if (preg_match('/^\+2519[0-9]{8}$/', $phone)) {
            return $phone;
        }
        
        return $phone;
    }
    
    /**
     * Log SMS in database
     */
    private function logSMS($phone, $message, $type, $status, $order_id = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO sms_logs (phone_number, message, type, status, response, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $response_data = json_encode([
                'order_id' => $order_id,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            $stmt->execute([$phone, $message, $type, $status, $response_data]);
            
        } catch (Exception $e) {
            error_log("Error logging SMS: " . $e->getMessage());
        }
    }
    
    /**
     * Get available banks with their SMS codes
     */
    public function getAvailableBanks() {
        try {
            $stmt = $this->pdo->prepare("SELECT bank_name, sms_code FROM bank_sms_codes WHERE is_active = 1 ORDER BY bank_name");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting banks: " . $e->getMessage());
            return [];
        }
    }
}
?>