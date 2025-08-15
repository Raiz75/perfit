<?php
// --- OPTIONAL: If you add a secret later ---
// $secret = 'PUT-YOUR-SECRET-HERE';
// $payload = file_get_contents('php://input');
// $signature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';
// if (!hash_equals('sha1=' . hash_hmac('sha1', $payload, $secret), $signature)) {
//     http_response_code(403);
//     exit('Invalid signature');
// }

// --- Deployment command ---
//cd /home/username/domains/domainName.com/path_to_html && git pull 2>&1
$output = shell_exec('cd /home/u634069814/domains/darkred-camel-628204.hostingersite.com/public_html/perfit && git pull 2>&1');
echo "<pre>$output</pre>";
?>