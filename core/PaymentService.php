<?php
/**
 * Payment & Order Service
 */

require_once __DIR__ . '/Database.php';

class PaymentService {

    public static function getSetting(string $key, $default = null) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT setting_value FROM platform_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return ($val !== false) ? $val : $default;
    }

    public static function setSetting(string $key, string $value): void {
        $db = Database::getConnection();
        $isMysql = (DB_DRIVER === 'mysql');
        if ($isMysql) {
            $stmt = $db->prepare("INSERT INTO platform_settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
        } else {
            $stmt = $db->prepare("INSERT OR REPLACE INTO platform_settings (setting_key, setting_value, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
        }
        $stmt->execute([$key, $value]);
    }

    public static function getAllSettings(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT setting_key, setting_value FROM platform_settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    public static function isPaymentRequired(): bool {
        return self::getSetting('payments_enabled', '0') === '1';
    }

    public static function getPrice(): float {
        return (float)self::getSetting('payment_price', '149');
    }

    public static function getCurrency(): string {
        return self::getSetting('payment_currency', 'EGP');
    }

    /**
     * Submit a manual payment proof (InstaPay or Vodafone Cash)
     */
    public static function submitManualPayment(int $giftId, int $userId, string $provider, string $reference, ?string $receiptPath = null): array {
        $db = Database::getConnection();
        $amount = self::getPrice();
        $currency = self::getCurrency();

        // 1. Create or retrieve pending order
        $stmt = $db->prepare("SELECT id FROM orders WHERE gift_id = ? AND user_id = ? AND status = 'pending' LIMIT 1");
        $stmt->execute([$giftId, $userId]);
        $orderId = $stmt->fetchColumn();

        if (!$orderId) {
            $insOrder = $db->prepare("INSERT INTO orders (user_id, gift_id, amount, currency, status) VALUES (?, ?, ?, ?, 'pending')");
            $insOrder->execute([$userId, $giftId, $amount, $currency]);
            $orderId = (int)$db->lastInsertId();
        }

        // 2. Insert Payment Record
        $insPayment = $db->prepare("INSERT INTO payments (
            order_id, gift_id, user_id, provider, transaction_reference, amount, currency, status, receipt_path
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'awaiting_review', ?)");
        $insPayment->execute([$orderId, $giftId, $userId, $provider, $reference, $amount, $currency, $receiptPath]);
        $paymentId = (int)$db->lastInsertId();

        // 3. Update gift payment status to pending_review
        $db->prepare("UPDATE gift_websites SET payment_status = 'pending_review' WHERE id = ?")->execute([$giftId]);

        return [
            'success' => true,
            'payment_id' => $paymentId,
            'order_id' => $orderId,
            'status' => 'awaiting_review'
        ];
    }

    /**
     * Approve a payment (Admin action)
     */
    public static function approvePayment(int $paymentId, int $adminUserId, ?string $notes = null): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM payments WHERE id = ? LIMIT 1");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();

        if (!$payment) {
            return false;
        }

        $isMysql = (DB_DRIVER === 'mysql');
        $now = $isMysql ? 'NOW()' : 'CURRENT_TIMESTAMP';

        // Update payment
        $stmt = $db->prepare("UPDATE payments SET status = 'paid', reviewed_by = ?, reviewed_at = $now, admin_notes = ? WHERE id = ?");
        $stmt->execute([$adminUserId, $notes, $paymentId]);

        // Update order
        if (!empty($payment['order_id'])) {
            $db->prepare("UPDATE orders SET status = 'paid' WHERE id = ?")->execute([$payment['order_id']]);
        }

        // Update gift payment status to paid
        $db->prepare("UPDATE gift_websites SET payment_status = 'paid' WHERE id = ?")->execute([$payment['gift_id']]);

        return true;
    }

    /**
     * Reject a payment (Admin action)
     */
    public static function rejectPayment(int $paymentId, int $adminUserId, ?string $reason = null): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM payments WHERE id = ? LIMIT 1");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();

        if (!$payment) {
            return false;
        }

        $isMysql = (DB_DRIVER === 'mysql');
        $now = $isMysql ? 'NOW()' : 'CURRENT_TIMESTAMP';

        // Update payment
        $stmt = $db->prepare("UPDATE payments SET status = 'rejected', reviewed_by = ?, reviewed_at = $now, admin_notes = ? WHERE id = ?");
        $stmt->execute([$adminUserId, $reason, $paymentId]);

        // Update gift status to unpaid
        $db->prepare("UPDATE gift_websites SET payment_status = 'unpaid' WHERE id = ?")->execute([$payment['gift_id']]);

        return true;
    }

    /**
     * List payments for admin queue
     */
    public static function listPayments(?string $status = null): array {
        $db = Database::getConnection();
        $sql = "SELECT p.*, g.title as gift_title, g.slug as gift_slug, u.username as user_name, u.email as user_email 
                FROM payments p 
                JOIN gift_websites g ON p.gift_id = g.id 
                JOIN users u ON p.user_id = u.id ";
        $params = [];
        if ($status) {
            $sql .= "WHERE p.status = ? ";
            $params[] = $status;
        }
        $sql .= "ORDER BY p.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getPendingPayments(): array {
        return self::listPayments('awaiting_review');
    }
}
