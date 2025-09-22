<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');

include('../_connect.php');
include('../../includes/helpers/short.php');

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
    $api_key = $_POST['api_key'];
else $api_key = null;

//brand_id
if(isset($_POST['brand_id']) && is_numeric($_POST['brand_id']))
    $brand_id = (int)$_POST['brand_id'];
else $brand_id = null;

//campaign_id
if(isset($_POST['campaign_id']) && is_numeric($_POST['campaign_id']))
    $campaign_id = (int)$_POST['campaign_id'];
else $campaign_id = null;

//label (for backward compatibility)
if(isset($_POST['label']))
    $label = $_POST['label'];
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
    // Use campaign_id directly (prepared statement)
    $stmt = $mysqli->prepare('SELECT id, to_send, opens, label, sent, app FROM campaigns WHERE id = ?');
    $stmt->bind_param('i', $campaign_id);
    $stmt->execute();
    $r = $stmt->get_result();
} else {
    // Use brand_id + label (legacy method with prepared statement)
    if($brand_id==null) {
        echo $error_passed[0];
        exit;
    }
    $stmt = $mysqli->prepare('SELECT id, to_send, opens, label, sent, app FROM campaigns WHERE app = ? AND label = ?');
    $stmt->bind_param('is', $brand_id, $label);
    $stmt->execute();
    $r = $stmt->get_result();
}

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
$opens = stripslashes($data['opens']);
$opens_array = explode(',', $opens);

$data_opens = array();
$data_country = array();

foreach ($opens_array as $open) {
    if(trim($open) !== '') {
        list($id, $country) = explode(':', $open);
        if (!isset($data_opens[$id])) {
            $data_opens[$id] = 0;
        }
        $data_opens[$id]++;
        
        if (!isset($data_country[$country])) {
            $data_country[$country] = 0;
        }
        $data_country[$country]++;
    }
}

$output = array(
    'total_opens' => count($opens_array),
    'unique_opens' => count($data_opens),
    'country_opens' => $data_country,
    'total_sent' => $data['to_send'],
    'brand_id' => $data['app'],
    'label' => $data['label'],
    'campaign_id' => $data['id']
);

echo json_encode($output, JSON_PRETTY_PRINT);
exit;
?>