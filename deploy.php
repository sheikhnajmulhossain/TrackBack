<?php
// Deploy Script - Called by GitHub Webhook
$secret = 'K7#mP9$xL2@Q5!vN3&dR6*jF1%bZ4~cS'; // Change this to something random

// Verify webhook signature
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$hash = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($hash, $signature)) {
    http_response_code(403);
    die('Unauthorized');
}

// Pull latest changes
$output = shell_exec('cd /home/trackback/public_html/trackback && git pull origin main 2>&1');
file_put_contents('/tmp/deploy.log', date('Y-m-d H:i:s') . ' - ' . $output . "\n", FILE_APPEND);

http_response_code(200);
echo json_encode(['status' => 'deployed', 'output' => $output]);
?>