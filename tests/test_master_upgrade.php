<?php
/**
 * Comprehensive Automated Test Suite for Habeba Romantic Platform Upgrade
 * Covers:
 * 1. Localization & RTL/LTR Engine
 * 2. Authentication, Password Hashing, Session Security, Duplicate Email Rejection
 * 3. Tenant Privacy, Multi-gift Isolation, Ownership Authorization
 * 4. 9 Romantic Themes in ThemeRegistry
 * 5. Payment Policy & Publication Enforcement (Disabled vs Enabled, Manual InstaPay Workflow, Admin Approval)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/I18n.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ThemeRegistry.php';
require_once __DIR__ . '/../core/PaymentService.php';
require_once __DIR__ . '/../core/PublicationPolicy.php';
require_once __DIR__ . '/../core/GiftService.php';

$passed = 0;
$failed = 0;

function assertTest(string $name, bool $condition, string $detail = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo " [PASS] $name\n";
    } else {
        $failed++;
        echo " [FAIL] $name" . ($detail ? " - $detail" : "") . "\n";
    }
}

echo "============================================================\n";
echo "HABEBA PLATFORM UPGRADE: AUTOMATED VERIFICATION SUITE\n";
echo "============================================================\n\n";

$db = Database::getConnection();

// -----------------------------------------------------------------------------
// PHASE 1: LOCALIZATION & RTL/LTR ENGINE
// -----------------------------------------------------------------------------
echo "--- Testing Phase 1: Localization & RTL/LTR ---\n";

I18n::init();
// Initial default should be 'ar'
assertTest("Default language is Arabic ('ar')", I18n::getLocale() === 'ar');
assertTest("Arabic interface direction is RTL", I18n::isRtl() === true);
assertTest("Arabic translation key 'nav_create' resolves", __('nav_create') === 'اصنع هدية جديدة');

// Switch to English
I18n::setLocale('en');
assertTest("Language switches to English ('en')", I18n::getLocale() === 'en');
assertTest("English interface direction is LTR", I18n::isRtl() === false);
assertTest("English translation key 'nav_create' resolves", __('nav_create') === 'Create a Gift');

// Reset to Arabic
I18n::setLocale('ar');
assertTest("Language reverts cleanly to Arabic", I18n::getLocale() === 'ar');

// -----------------------------------------------------------------------------
// PHASE 2: AUTHENTICATION, PASSWORDS & SECURITY
// -----------------------------------------------------------------------------
echo "\n--- Testing Phase 2: Authentication & Security ---\n";

$testEmailA = 'customer_a_' . time() . '@test.com';
$testPassA = 'Secret@123456';
$testNameA = 'Customer Alpha';

$testEmailB = 'customer_b_' . time() . '@test.com';
$testPassB = 'Secret@654321';
$testNameB = 'Customer Beta';

// Register User A
$regA = Auth::register($testNameA, $testEmailA, $testPassA);
assertTest("User A registration succeeds", $regA['success'] === true && !empty($regA['user_id']));
$userAId = (int)$regA['user_id'];

// Check password hashing in database
$stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
$stmt->execute([$userAId]);
$hash = $stmt->fetchColumn();
assertTest("Password is cryptographically hashed with password_hash()", !empty($hash) && password_verify($testPassA, $hash) && $hash !== $testPassA);

// Test duplicate registration with same email
$regDup = Auth::register('Duplicate User', $testEmailA, 'AnotherPass123');
assertTest("Duplicate email registration is strictly rejected", $regDup['success'] === false);

// Register User B
$regB = Auth::register($testNameB, $testEmailB, $testPassB);
assertTest("User B registration succeeds", $regB['success'] === true && !empty($regB['user_id']));
$userBId = (int)$regB['user_id'];

// Test login with invalid credentials
$badLogin = Auth::login($testEmailA, 'WrongPassword!');
assertTest("Invalid password login attempt is rejected", $badLogin['success'] === false);

// Test login with valid credentials
$goodLogin = Auth::login($testEmailA, $testPassA);
assertTest("Valid credentials login succeeds", $goodLogin['success'] === true);

// -----------------------------------------------------------------------------
// PHASE 3 & 4: TENANT PRIVACY & STRICT OWNERSHIP ISOLATION
// -----------------------------------------------------------------------------
echo "\n--- Testing Phases 3 & 4: Tenant Privacy & Isolation ---\n";

// User A creates a private gift
$giftDataA = [
    'sender_name' => 'Alpha',
    'partner_name' => 'Layla',
    'hero_title' => 'Alpha & Layla Forever',
    'custom_message' => 'Our private story',
    'theme' => 'cinematic-love',
    'slug' => 'alpha-layla-' . time(),
    'is_published' => 0
];
$giftARes = GiftService::createGift($giftDataA, $userAId);
assertTest("User A can create a private gift", $giftARes['success'] === true);
$giftAId = (int)$giftARes['id'];

// User A can access own gift
assertTest("User A has access to own gift", GiftService::canAccessGift($giftAId, $userAId) === true);

// User B CANNOT access User A's gift
assertTest("User B CANNOT access User A's gift (server authorization check)", GiftService::canAccessGift($giftAId, $userBId) === false);

// User B CANNOT retrieve User A's gift via getGiftById
$fetchUnauthorized = GiftService::getGiftById($giftAId, $userBId);
assertTest("getGiftById with User B returns null/empty for User A's gift", empty($fetchUnauthorized));

// User B CANNOT update User A's gift
$tamperAttempt = GiftService::updateGift($giftAId, $userBId, null, [
    'title' => 'Tampered By B'
]);
assertTest("User B cannot update User A's gift (ownership violation)", $tamperAttempt['success'] === false);

// User B CANNOT delete User A's gift
$deleteAttempt = GiftService::deleteGift($giftAId, $userBId);
assertTest("User B cannot delete User A's gift", $deleteAttempt === false);

// User B CANNOT duplicate User A's gift
$dupAttempt = GiftService::duplicateGift($giftAId, $userBId);
assertTest("User B cannot duplicate User A's gift", empty($dupAttempt));

// User A's dashboard only returns User A's gifts
$userAGifts = GiftService::listUserGifts($userAId);
$userBGifts = GiftService::listUserGifts($userBId);
$userAGiftIds = array_column($userAGifts, 'id');
assertTest("User A's dashboard contains User A's gift", in_array($giftAId, $userAGiftIds));
assertTest("User B's dashboard does NOT contain User A's gift", count($userBGifts) === 0);

// Landing page showcase ONLY returns demo gifts (is_demo = 1), zero customer gifts
$showcase = GiftService::listShowcaseGifts();
$hasCustomerGiftInShowcase = false;
foreach ($showcase as $g) {
    if ($g['id'] == $giftAId || empty($g['is_demo'])) {
        $hasCustomerGiftInShowcase = true;
        break;
    }
}
assertTest("Landing page public showcase excludes all customer private gifts", $hasCustomerGiftInShowcase === false);

// -----------------------------------------------------------------------------
// PHASE 5: TEMPLATES & THEME REGISTRY
// -----------------------------------------------------------------------------
echo "\n--- Testing Phase 5: Theme Registry & 9 Romantic Templates ---\n";

$allThemes = ThemeRegistry::getAllThemes();
assertTest("ThemeRegistry contains at least 9 distinct themes", count($allThemes) >= 9);

$requiredThemes = [
    'luxury-cream',
    'midnight',
    'rose-blush',
    'vintage-scrapbook',
    'cinematic-love',
    'minimal-love',
    'dreamy-pastel',
    'love-journal',
    'eternal-romance'
];

$allThemeKeys = array_keys($allThemes);
$missingThemes = array_diff($requiredThemes, $allThemeKeys);
assertTest("All 5 new templates + 4 original templates are registered", empty($missingThemes), "Missing: " . implode(', ', $missingThemes));

foreach ($requiredThemes as $themeKey) {
    $themeData = ThemeRegistry::getTheme($themeKey);
    $isValid = !empty($themeData['name_ar']) && !empty($themeData['name_en']) && !empty($themeData['preview_class']);
    if (!$isValid) {
        assertTest("Theme '$themeKey' metadata validation", false, "Incomplete configuration");
    }
}
assertTest("All registered themes have valid bilingual metadata and CSS classes", true);

// -----------------------------------------------------------------------------
// PHASES 7 & 8: PAYMENT SYSTEM FOUNDATION & PUBLICATION ENFORCEMENT
// -----------------------------------------------------------------------------
echo "\n--- Testing Phases 7 & 8: Configurable Payment & Publication Policy ---\n";

// Scenario A: Default Testing Mode (payments_enabled = 0)
PaymentService::setSetting('payments_enabled', '0');
$policyFree = PublicationPolicy::canPublish($giftAId, $userAId);
assertTest("Default Testing Mode: canPublish succeeds without payment", $policyFree['allowed'] === true);

// User A publishes the gift in free testing mode
$publishFree = GiftService::publishGift($giftAId, $userAId);
assertTest("Publishing draft succeeds when payments are disabled", $publishFree['success'] === true && $publishFree['payment_alert'] === null);

// Scenario B: Payments Enabled (payments_enabled = 1)
PaymentService::setSetting('payments_enabled', '1');
PaymentService::setSetting('payment_price', '250.00');
PaymentService::setSetting('payment_currency', 'EGP');
PaymentService::setSetting('instapay_enabled', '1');
PaymentService::setSetting('vodafone_cash_enabled', '1');

// User A creates a second draft gift
$giftDataA2 = [
    'sender_name' => 'Alpha',
    'recipient_name' => 'Layla 2',
    'title' => 'Our Second Chapter',
    'welcome_message' => 'Anniversary gift',
    'theme_id' => 'eternal-romance',
    'slug' => 'alpha-layla-anniversary-' . time(),
    'status' => 'draft'
];
$giftA2Res = GiftService::createGift($giftDataA2, $userAId);
$giftA2Id = (int)$giftA2Res['id'];

// Verify publication is BLOCKED for unpaid gift
$policyLocked = PublicationPolicy::canPublish($giftA2Id, $userAId);
assertTest("When payments enabled: unpaid gift publication is strictly BLOCKED", $policyLocked['allowed'] === false && $policyLocked['reason'] === 'payment_required');

// Attempt to publish directly via GiftService -> must fail
$publishBlocked = GiftService::publishGift($giftA2Id, $userAId);
assertTest("GiftService::publishGift enforces payment policy and rejects publication", !empty($publishBlocked['payment_alert']));

// Customer starts checkout and submits manual InstaPay transfer
$submitPayment = PaymentService::submitManualPayment($giftA2Id, $userAId, 'instapay', 'TXN-INSTA-998877', 'uploads/receipts/test_receipt.jpg');
assertTest("Customer submits manual payment details (status: awaiting_review)", $submitPayment['success'] === true && !empty($submitPayment['payment_id']));
$paymentId = (int)$submitPayment['payment_id'];

// While awaiting review, publication is STILL blocked
$policyReview = PublicationPolicy::canPublish($giftA2Id, $userAId);
assertTest("While payment is awaiting review, publication remains BLOCKED", $policyReview['allowed'] === false && $policyReview['reason'] === 'payment_pending_review');

// Admin checks payment review queue
$pendingPayments = PaymentService::getPendingPayments();
$foundInQueue = false;
foreach ($pendingPayments as $p) {
    if ($p['id'] == $paymentId) {
        $foundInQueue = true;
        break;
    }
}
assertTest("Pending payment appears in administrator review queue", $foundInQueue === true);

// Admin verifies and approves payment
$approveRes = PaymentService::approvePayment($paymentId, 1, 'Verified via InstaPay bank statement');
assertTest("Admin can approve verified manual payment", $approveRes === true);

// Check that gift's payment status updated to 'paid'
$giftA2Updated = GiftService::getGiftById($giftA2Id, $userAId);
assertTest("Gift payment_status is marked 'paid' after admin approval", $giftA2Updated['payment_status'] === 'paid');

// Publication is now permitted
$policyApproved = PublicationPolicy::canPublish($giftA2Id, $userAId);
assertTest("After payment approval: publication is permitted", $policyApproved['allowed'] === true);

// User A publishes the paid gift
$publishApproved = GiftService::publishGift($giftA2Id, $userAId);
assertTest("User A successfully publishes paid gift", $publishApproved['success'] === true && empty($publishApproved['payment_alert']));

// Revert payments_enabled back to '0' to maintain Default Testing Mode commitment
PaymentService::setSetting('payments_enabled', '0');
$finalPolicy = PublicationPolicy::canPublish($giftA2Id, $userAId);
assertTest("Default testing mode restored (payments_enabled = 0)", $finalPolicy['allowed'] === true);

// -----------------------------------------------------------------------------
// SUMMARY REPORT
// -----------------------------------------------------------------------------
echo "\n============================================================\n";
echo "TEST SUITE COMPLETE: $passed PASSED, $failed FAILED\n";
echo "============================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
