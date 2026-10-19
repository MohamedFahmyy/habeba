<?php
$sampleImage = __DIR__ . '/../assets/images/6.jpg';
if (!file_exists($sampleImage)) {
    die("Sample image not found\n");
}

$cfile = new CURLFile($sampleImage, 'image/jpeg', 'test_upload.jpg');
$ch = curl_init('http://127.0.0.1:8088/api/upload.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['image' => $cfile]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$res = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($res, true);
if ($httpCode === 200 && !empty($data['success'])) {
    echo "✅ [Upload Test] -> Image uploaded successfully: " . $data['url'] . "\n";
    if (file_exists(__DIR__ . '/../' . $data['url'])) {
        echo "✅ [Upload Test] -> File confirmed on disk: " . $data['filename'] . "\n";
        // Clean up test upload
        @unlink(__DIR__ . '/../' . $data['url']);
        echo "✅ [Upload Test] -> Test upload cleaned up.\n";
    }
} else {
    echo "❌ [Upload Test] -> Upload failed: Status {$httpCode}, Response: " . $res . "\n";
    exit(1);
}
