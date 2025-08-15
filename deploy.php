<?php
// // Optional: Verify request is from GitHub using secret
// $secret = "perfit"; // must match what you add in GitHub webhook
// $rawPost = file_get_contents('php://input');
// $headers = getallheaders();
// $signature = $headers['X-Hub-Signature-256'] ?? '';

// if ($secret) {
//     $hash = 'sha256=' . hash_hmac('sha256', $rawPost, $secret);
//     if (!hash_equals($hash, $signature)) {
//         http_response_code(403);
//         echo "Invalid signature.";
//         exit;
//     }
// }

// // Go to repo directory and pull latest changes
// $repoDir = '/home/u634069814/domains/darkred-camel-628204.hostingersite.com/public_html/perfit_folder';//test3
// chdir($repoDir);
// $output = shell_exec('git pull origin main 2>&1');

// echo "Deployment output:\n" . $output;
?>