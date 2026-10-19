<?php
/**
 * Centralized Publication Policy Engine
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/PaymentService.php';

class PublicationPolicy {

    public static function canPublish(int $giftId, ?int $userId = null): array {
        // If payments are disabled in platform settings (default testing mode)
        if (!PaymentService::isPaymentRequired()) {
            return ['allowed' => true, 'status' => 'free_mode'];
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, payment_status, is_demo, user_id FROM gift_websites WHERE id = ? LIMIT 1");
        $stmt->execute([$giftId]);
        $gift = $stmt->fetch();

        if (!$gift) {
            return ['allowed' => false, 'reason' => 'gift_not_found'];
        }

        // Demo / Flagship or previously paid gifts are exempt
        if (!empty($gift['is_demo']) || $gift['payment_status'] === 'paid') {
            return ['allowed' => true, 'status' => 'paid'];
        }

        // Check if there is an approved order
        if ($userId) {
            $orderStmt = $db->prepare("SELECT id FROM orders WHERE gift_id = ? AND user_id = ? AND status = 'paid' LIMIT 1");
            $orderStmt->execute([$giftId, $userId]);
            if ($orderStmt->fetch()) {
                return ['allowed' => true, 'status' => 'paid'];
            }
        }

        // Check if payment is awaiting review
        if ($gift['payment_status'] === 'pending_review') {
            return [
                'allowed' => false,
                'reason' => 'payment_pending_review',
                'message' => 'تحويل الدفع قيد المراجعة من الإدارة. سيتاح النشر بمجرد الموافقة.',
                'checkout_url' => BASE_URL . '/checkout.php?gift_id=' . $giftId
            ];
        }

        return [
            'allowed' => false,
            'reason' => 'payment_required',
            'message' => 'يتطلب نشر هذا الموقع سداد رسوم التفعيل أولاً.',
            'checkout_url' => BASE_URL . '/checkout.php?gift_id=' . $giftId
        ];
    }
}
