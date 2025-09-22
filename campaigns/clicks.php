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
    $q = 'SELECT id, app FROM campaigns WHERE id = '.$campaign_id;
} else {
    // Use brand_id + label (legacy method)
    if($brand_id==null) {
        echo $error_passed[0];
        exit;
    }
    $q = 'SELECT id, app FROM campaigns WHERE app = '.$brand_id.' AND label = "'.$label.'"';
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

$campaign_data = mysqli_fetch_assoc($r);
$campaign_id = $campaign_data['id'];
$brand_id = $campaign_data['app'];

// Get detailed click data
$link_query = 'SELECT LEFT(REPLACE(link,query_string,""),CHAR_LENGTH(REPLACE(link,query_string,"")) -1) AS url, clicks FROM links WHERE campaign_id = "'.$campaign_id.'"';
$link_result = mysqli_query($mysqli, $link_query);

$output = array();

if ($link_result !== false) {
    while ($link_data = mysqli_fetch_assoc($link_result)) {
        $clicks = $link_data['clicks'];
        $click_count = 0;
        
        if($clicks && trim($clicks) !== '') {
            $clicks_array = explode(',', $clicks);
            $click_count = count($clicks_array);
        }
        
        $output[] = array(
            'url' => $link_data['url'],
            'clicks' => $click_count
        );
    }
}

echo json_encode($output, JSON_PRETTY_PRINT);
exit;
?>