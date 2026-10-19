<?php
$urls = [
    'Homepage Hub' => 'http://127.0.0.1:8088/index.php',
    'Creation Wizard' => 'http://127.0.0.1:8088/wizard.php',
    'Public Gift (Mohamed & Salma)' => 'http://127.0.0.1:8088/gift.php?slug=salma-and-mohamed',
    'Visual Editor' => 'http://127.0.0.1:8088/editor.php?id=1&token=flagship_token_salma_mohamed',
    'Admin Panel' => 'http://127.0.0.1:8088/admin.php'
];

$allPassed = true;
foreach ($urls as $name => $url) {
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $content = @file_get_contents($url, false, $ctx);
    $status = $http_response_header[0] ?? 'NO RESPONSE';
    $len = strlen($content);
    $isOk = strpos($status, '200') !== false && $len > 1000;
    
    if ($isOk) {
        echo "✅ [{$name}] -> Status: {$status} | Size: {$len} bytes\n";
    } else {
        echo "❌ [{$name}] -> FAILED: {$status} | Size: {$len} bytes\n";
        $allPassed = false;
    }
}

if ($allPassed) {
    echo "\n🎉 ALL HTTP ENDPOINTS ARE FULLY OPERATIONAL AND RETURNING HTTP 200!\n";
    exit(0);
} else {
    echo "\n⚠️ SOME ENDPOINTS FAILED\n";
    exit(1);
}
