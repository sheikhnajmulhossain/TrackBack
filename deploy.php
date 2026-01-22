<?php
// Deploy Script - Called by GitHub Webhook
$secret = 'K7#mP9$xL2@Q5!vN3&dR6*jF1%bZ4~cS';

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    exit('Unauthorized');
}

$repoPath = '/home/trackback/public_html';

// Use full git path (common fix on cPanel)
$git = '/usr/bin/git';

$cmd = "cd " . escapeshellarg($repoPath) . " && $git pull origin main 2>&1";
$output = shell_exec($cmd);

file_put_contents($repoPath . '/deploy.log', date('Y-m-d H:i:s') . " - " . $output . PHP_EOL, FILE_APPEND);

http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['status' => 'deployed', 'output' => $output]);
