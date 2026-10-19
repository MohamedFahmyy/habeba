<?php
/**
 * Romantic Memories Platform - Automated Verification Test Suite
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/GiftService.php';

function assertTest(bool $condition, string $testName) {
    if ($condition) {
        echo "✅ PASS: $testName\n";
    } else {
        echo "❌ FAIL: $testName\n";
        exit(1);
    }
}

echo "=== STARTING ROMANTIC MEMORIES VERIFICATION SUITE ===\n\n";

// 1. YouTube ID Extraction Tests
echo "--- Testing YouTube URL Extraction ---\n";
$yt1 = GiftService::extractYouTubeId('https://www.youtube.com/watch?v=g7Zw5x6GKlE');
assertTest($yt1 === 'g7Zw5x6GKlE', 'Standard watch?v= URL extraction');

$yt2 = GiftService::extractYouTubeId('https://youtu.be/g7Zw5x6GKlE?si=xyz123');
assertTest($yt2 === 'g7Zw5x6GKlE', 'Shortened youtu.be URL extraction');

$yt3 = GiftService::extractYouTubeId('https://www.youtube.com/shorts/g7Zw5x6GKlE');
assertTest($yt3 === 'g7Zw5x6GKlE', 'YouTube Shorts URL extraction');

$yt4 = GiftService::extractYouTubeId('g7Zw5x6GKlE');
assertTest($yt4 === 'g7Zw5x6GKlE', 'Raw 11-char video ID extraction');

// 2. Database & Flagship Seed
echo "\n--- Testing Database & Flagship Gift ---\n";
$db = Database::getConnection();
assertTest($db instanceof PDO, 'Database connection is PDO instance');

$flagship = GiftService::getGiftBySlug('salma-and-mohamed');
assertTest($flagship !== null, 'Flagship gift (salma-and-mohamed) exists');
assertTest($flagship['sender_name'] === 'Mohamed', 'Flagship sender is Mohamed');
assertTest($flagship['recipient_name'] === 'Salma', 'Flagship recipient is Salma');
assertTest(count($flagship['memories']) >= 5, 'Flagship contains at least 5 memories');
assertTest(count($flagship['timeline']) >= 2, 'Flagship contains timeline entries');
assertTest(count($flagship['counters']) >= 4, 'Flagship contains counters');

// 3. New Gift Creation Wizard Simulation
echo "\n--- Testing Gift Creation Workflow ---\n";
$testData = [
    'sender_name' => 'Kareem',
    'recipient_name' => 'Nour',
    'title' => 'Kareem & Nour - Our Endless Journey',
    'welcome_message' => 'To the love of my life, every moment with you is magic.',
    'theme_id' => 'luxury-cream',
    'gallery_style' => 'polaroid',
    'music_url' => 'https://www.youtube.com/watch?v=g7Zw5x6GKlE',
    'music_title' => 'Our Song',
    'letter_title' => 'A Letter For Nour',
    'letter_content' => 'Nour, you made my world complete. I promise to cherish every second by your side.',
    'letter_signature' => 'Forever Yours, Kareem',
    'surprise_title' => 'Until The End Of Time',
    'surprise_message' => 'Happy Anniversary my love!',
    'status' => 'published',
    'memories' => [
        ['image_url' => 'assets/images/6.jpg', 'title' => 'First Day', 'caption' => 'The coffee that changed our lives', 'memory_date' => 'Day 1'],
        ['image_url' => 'assets/images/7.jpg', 'title' => 'Quiet Stroll', 'caption' => 'Just you, me, and the ocean breeze', 'memory_date' => 'Day 20'],
        ['image_url' => 'assets/images/8.jpg', 'title' => 'Sunset Dreams', 'caption' => 'Watching the colors fade into night', 'memory_date' => 'Day 50'],
        ['image_url' => 'assets/images/11.jpg', 'title' => 'Unstoppable Laughs', 'caption' => 'When we couldn’t stop smiling', 'memory_date' => 'Day 100'],
        ['image_url' => 'assets/images/12.jpg', 'title' => 'Special Milestone', 'caption' => 'Celebrating another month together', 'memory_date' => 'Day 150'],
    ],
    'timeline' => [
        ['title' => 'When We Met', 'description' => 'A casual conversation that turned into a soul connection', 'event_date' => 'January 10, 2024'],
        ['title' => 'Official Date', 'description' => 'Our unforgettable dinner by the bay', 'event_date' => 'February 14, 2024']
    ]
];

$created = GiftService::createGift($testData);
assertTest(!empty($created['id']), 'Created gift returned positive integer ID');
assertTest(!empty($created['slug']), 'Created gift generated unique slug');
assertTest(!empty($created['edit_token']), 'Created gift generated security edit_token');

// 4. Fetch and Verify Created Gift
echo "\n--- Testing Created Gift Retrieval ---\n";
$fetched = GiftService::getGiftBySlug($created['slug']);
assertTest($fetched !== null, 'Fetched gift by unique slug');
assertTest($fetched['sender_name'] === 'Kareem', 'Sender name matches');
assertTest($fetched['recipient_name'] === 'Nour', 'Recipient name matches');
assertTest($fetched['music_video_id'] === 'g7Zw5x6GKlE', 'YouTube video ID parsed correctly');
assertTest(count($fetched['memories']) === 5, 'Exact 5 initial memory cards saved');
assertTest(count($fetched['timeline']) === 2, '2 timeline milestones saved');

// 5. Update via Visual Editor Simulation
echo "\n--- Testing Visual Editor Update ---\n";
$updateData = [
    'title' => 'Kareem & Nour - Updated Love Story',
    'theme_id' => 'midnight',
    'memories' => [
        ['image_url' => 'assets/images/14.jpg', 'title' => 'Added New Memory', 'caption' => 'Memory added via editor', 'memory_date' => 'Day 200']
    ]
];

$updated = GiftService::updateGift($created['id'], $created['edit_token'], $updateData);
assertTest($updated === true, 'Gift updated with valid edit_token');

$refetched = GiftService::getGiftById($created['id'], $created['edit_token']);
assertTest($refetched['title'] === 'Kareem & Nour - Updated Love Story', 'Updated title persisted');
assertTest($refetched['theme_id'] === 'midnight', 'Theme updated to midnight');
assertTest(count($refetched['memories']) === 1, 'Memories replaced/updated accurately');

// 6. Security & Unauthorized Edit Block
echo "\n--- Testing Authorization & Security Checks ---\n";
$fakeTokenUpdate = GiftService::updateGift($created['id'], 'fake_token_123', ['title' => 'Hacked Title']);
assertTest($fakeTokenUpdate === false, 'Unauthorized update with fake token was blocked');

// 7. Cleanup test gift
echo "\n--- Cleaning up test record ---\n";
$deleted = GiftService::deleteGift($created['id'], $created['edit_token']);
assertTest($deleted === true, 'Test gift deleted cleanly');

echo "\n🎉 ALL VERIFICATION TESTS COMPLETED SUCCESSFULLY!\n";
