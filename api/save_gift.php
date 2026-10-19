<?php
/**
 * Save Gift API Handler (Draft / Publish / Authorization Enforced)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/I18n.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/GiftService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

if (empty($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Empty payload received.']);
    exit;
}

$giftId = isset($data['id']) ? (int)$data['id'] : 0;
$editToken = trim($data['edit_token'] ?? '');
$userId = Auth::userId();

try {
    if ($giftId > 0) {
        // Update existing gift with strict tenant isolation check
        $updateResult = GiftService::updateGift($giftId, $userId, $editToken, $data);
        if (!$updateResult['success']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => $updateResult['error'] ?? 'Unauthorized: You do not own this gift.']);
            exit;
        }

        $gift = GiftService::getGiftById($giftId, $userId, $editToken);
        $res = [
            'success' => true,
            'message' => __('wiz_saved'),
            'id' => $giftId,
            'slug' => $gift['slug'],
            'url' => BASE_URL . '/gift.php?slug=' . urlencode($gift['slug']),
            'editor_url' => BASE_URL . '/editor.php?id=' . $giftId . '&token=' . $gift['edit_token'],
            'status' => $gift['status']
        ];

        if (!empty($updateResult['payment_alert'])) {
            $res['payment_alert'] = $updateResult['payment_alert'];
            $res['status'] = 'draft';
        }

        echo json_encode($res);
    } else {
        // Create new gift
        if (empty($data['sender_name']) || empty($data['recipient_name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Please provide both your name and your partner’s name.']);
            exit;
        }

        $result = GiftService::createGift($data, $userId);
        echo json_encode([
            'success' => true,
            'message' => __('wiz_saved'),
            'id' => $result['id'],
            'slug' => $result['slug'],
            'edit_token' => $result['edit_token'],
            'url' => $result['url'],
            'editor_url' => $result['editor_url'],
            'status' => $result['status'],
            'payment_alert' => $result['payment_alert'] ?? null
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
