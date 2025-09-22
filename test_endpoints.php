<?php
/**
 * Test Script for Sendy API Extensions
 * 
 * This script helps verify that your new campaign endpoints work correctly
 * before integrating with your sendy-api-utility
 */

// Configuration - UPDATE THESE VALUES
$sendy_url = 'http://staging.sendy.lifescicomms.com/sendy/';  // Your Sendy installation URL
$api_key = 'Pq32UwhWNvaf71ICrifa';            // Your API key from Sendy settings
$test_campaign_id = 123;                   // A campaign ID to test with
$test_brand_id = 1;                        // Your brand ID
$test_campaign_label = 'Test Campaign';    // Exact campaign label/name

echo "<h1>Sendy API Extension Test</h1>";
echo "<p>Testing the <strong>core campaign endpoints</strong> (required for sendy-api-utility)...</p>";

echo "<h2>✅ Core Endpoints Test</h2>";

// Test new summary endpoint with campaign_id
echo "<h2>Testing /api/campaigns/summary.php with campaign_id</h2>";
$result = test_endpoint('/api/campaigns/summary.php', [
    'api_key' => $api_key,
    'campaign_id' => $test_campaign_id
]);
echo "<pre>" . htmlspecialchars($result) . "</pre>";

// Test new summary endpoint with legacy parameters
echo "<h2>Testing /api/campaigns/summary.php with brand_id + label</h2>";
$result = test_endpoint('/api/campaigns/summary.php', [
    'api_key' => $api_key,
    'brand_id' => $test_brand_id,
    'label' => $test_campaign_label
]);
echo "<pre>" . htmlspecialchars($result) . "</pre>";

// Test clicks endpoint
echo "<h2>Testing /api/campaigns/clicks.php</h2>";
$result = test_endpoint('/api/campaigns/clicks.php', [
    'api_key' => $api_key,
    'campaign_id' => $test_campaign_id
]);
echo "<pre>" . htmlspecialchars($result) . "</pre>";

// Test opens endpoint  
echo "<h2>Testing /api/campaigns/opens.php</h2>";
$result = test_endpoint('/api/campaigns/opens.php', [
    'api_key' => $api_key,
    'campaign_id' => $test_campaign_id
]);
echo "<pre>" . htmlspecialchars($result) . "</pre>";

// Test enhanced query endpoint
echo "<h2>🚀 Optional Advanced Reporting Test</h2>";
echo "<p><em>This test requires the optional /api/reporting/ folder to be uploaded</em></p>";
echo "<h3>Testing enhanced /api/reporting/query.php with campaign_id</h3>";
$result = test_endpoint('/api/reporting/query.php', [
    'api_key' => $api_key,
    'campaign_id' => $test_campaign_id
]);
echo "<pre>" . htmlspecialchars(substr($result, 0, 500)) . "...</pre>";

function test_endpoint($endpoint, $data) {
    global $sendy_url;
    
    $url = rtrim($sendy_url, '/') . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return "CURL Error: " . $error;
    }
    
    if ($http_code !== 200) {
        return "HTTP Error: " . $http_code . "\nResponse: " . $response;
    }
    
    return $response;
}
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
h1, h2 { color: #333; }
pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>