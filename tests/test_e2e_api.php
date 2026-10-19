<?php
$data = [
    'sender_name' => 'Amr',
    'recipient_name' => 'Yara',
    'title' => 'Amr & Yara - A Tale of Romance',
    'welcome_message' => 'Every single day with you is my greatest gift.',
    'theme_id' => 'rose-blush',
    'gallery_style' => 'polaroid',
    'music_url' => 'https://www.youtube.com/watch?v=g7Zw5x6GKlE',
    'music_title' => 'Our Sweet Melody',
    'status' => 'published',
    'memories' => [
        ['image_url' => 'assets/images/15.jpg', 'title' => 'The Spark', 'caption' => 'The moment that changed everything', 'memory_date' => 'Day 1'],
        ['image_url' => 'assets/images/16.jpg', 'title' => 'Sea Breeze', 'caption' => 'Walking by the water', 'memory_date' => 'Day 40']
    ]
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
        'timeout' => 5
    ]
];
$context  = stream_context_create($options);
$result = file_get_contents('http://127.0.0.1:8088/api/save_gift.php', false, $context);
$response = json_decode($result, true);

if ($response && !empty($response['success'])) {
    echo "✅ [API Creation Test] -> Gift created successfully via HTTP POST!\n";
    echo "   ID: " . $response['id'] . "\n";
    echo "   Slug: " . $response['slug'] . "\n";
    echo "   Public URL: " . $response['url'] . "\n";
    
    // Now verify we can read it back via HTTP
    $page = file_get_contents($response['url']);
    if (strpos($page, 'Amr ❤️ Yara') !== false) {
        echo "✅ [Public Gift Page Test] -> Successfully loaded dynamic page with 'Amr ❤️ Yara'!\n";
    } else {
        echo "❌ [Public Gift Page Test] -> Could not find names in public page.\n";
        exit(1);
    }
} else {
    echo "❌ [API Creation Test] -> Failed to create gift via HTTP POST: " . $result . "\n";
    exit(1);
}
