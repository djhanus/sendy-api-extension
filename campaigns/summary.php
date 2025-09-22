<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');

include('../../_connect.php');
include('../../../includes/helpers/short.php');

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/New_York');

//-------------------------- ERRORS -------------------------//
$error_core = array('No data passed', 'API key not passed', 'Invalid API key');
$error_passed = array(
    'Brand ID not passed',
    'Campaign ID or Label not passed',
    'Campaign not found'
);
//-----------------------------------------------------------//

//--------------------------- POST --------------------------//
//api_key	
if(isset($_POST['api_key']))
    $api_key = mysqli_real_escape_string($mysqli, $_POST['api_key']);
else $api_key = null;

//brand_id
if(isset($_POST['brand_id']) && is_numeric($_POST['brand_id']))
    $brand_id = mysqli_real_escape_string($mysqli, $_POST['brand_id']);
else $brand_id = null;

//campaign_id
if(isset($_POST['campaign_id']) && is_numeric($_POST['campaign_id']))
    $campaign_id = mysqli_real_escape_string($mysqli, $_POST['campaign_id']);
else $campaign_id = null;

//label (for backward compatibility)
if(isset($_POST['label']))
    $label = mysqli_real_escape_string($mysqli, $_POST['label']);
else $label = null;

//-----------------------------------------------------------//

//----------------------- VERIFICATION ----------------------//
//Core data
if($api_key==null && $brand_id==null && $campaign_id==null && $label==null)
{
    echo $error_core[0];
    exit;
}
if($api_key==null)
{
    echo $error_core[1];
    exit;
}
else if(!verify_api_key($api_key))
{
    echo $error_core[2];
    exit;
}

//Passed data - need either campaign_id OR (brand_id + label)
if($campaign_id==null && ($brand_id==null || $label==null))
{
    echo $error_passed[1];
    exit;
}

//-----------------------------------------------------------//

// Build query based on available parameters
if($campaign_id != null) {
    // Use campaign_id directly
    $q = 'SELECT id, to_send, opens, label, sent, app FROM campaigns WHERE id = '.$campaign_id;
} else {
    // Use brand_id + label (legacy method)
    if($brand_id==null) {
        echo $error_passed[0];
        exit;
    }
    $q = 'SELECT id, to_send, opens, label, sent, app FROM campaigns WHERE app = '.$brand_id.' AND label = "'.$label.'"';
}

$r = mysqli_query($mysqli, $q);

if ($r === false) {
    error_log('MySQL query error: ' . mysqli_error($mysqli));
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed']);
    exit;
}

if (mysqli_num_rows($r) == 0) 
{
    echo $error_passed[2]; 
    exit;
}

$data = mysqli_fetch_assoc($r);
$campaign_id = $data['id'];
$brand_id = $data['app']; // Ensure we have brand_id for link queries
$opens = stripslashes($data['opens']);
$opens_array = explode(',', $opens);
$total_sent = $data['to_send'];

// Calculate opens
$total_opens = count($opens_array);
$data_opens = array();

foreach ($opens_array as $open) {
    if(trim($open) !== '') {
        list($id, $country) = explode(':', $open);
        if (!isset($data_opens[$id])) {
            $data_opens[$id] = 0;
        }
        $data_opens[$id]++;
    }
}

$unique_opens = count($data_opens);

// Get total clicks from links
$total_clicks = 0;
$link_query = 'SELECT clicks FROM links WHERE campaign_id = "'.$campaign_id.'"';
$link_result = mysqli_query($mysqli, $link_query);

if ($link_result !== false) {
    while ($link_data = mysqli_fetch_assoc($link_result)) {
        $clicks = $link_data['clicks'];
        if($clicks && trim($clicks) !== '') {
            $clicks_array = explode(',', $clicks);
            $total_clicks += count($clicks_array);
        }
    }
}

// Calculate unsubscribes (this would need additional logic based on your Sendy setup)
$unsubscribes = 0; // Placeholder - you may need to implement this based on your unsubscribe tracking

// Return in the format expected by your utility: "sent,opens,clicks,unsubscribes"
echo $total_sent.','.$unique_opens.','.$total_clicks.','.$unsubscribes;
exit;
?>