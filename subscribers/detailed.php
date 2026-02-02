<?php
// Enable error reporting only in development
if (defined('DEBUG') && DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', 'error_log.txt');
}

include('../_connect.php');
include('../../includes/helpers/short.php');

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/New_York');

//-------------------------- ERRORS -------------------------//
$error_core = array('No data passed', 'API key not passed', 'Invalid API key');
$error_passed = array('Brand ID not passed', 'Subscriber data not found');

//--------------------------- POST --------------------------//
// Parameter handling with validation
$api_key = isset($_POST['api_key']) ? $_POST['api_key'] : null;
$brand_id = (isset($_POST['brand_id']) && is_numeric($_POST['brand_id'])) ? (int)$_POST['brand_id'] : null;
$limit = (isset($_POST['limit']) && is_numeric($_POST['limit'])) ? (int)$_POST['limit'] : 100;
$offset = (isset($_POST['offset']) && is_numeric($_POST['offset'])) ? (int)$_POST['offset'] : 0;
$list_id = isset($_POST['list_id']) ? $_POST['list_id'] : null;

//----------------------- VERIFICATION ----------------------//
if($api_key==null) {
    echo json_encode(['status' => 'error', 'message' => $error_core[1]]);
    exit;
}
else if(!verify_api_key($api_key)) {
    echo json_encode(['status' => 'error', 'message' => $error_core[2]]);
    exit;
}

if($brand_id==null) {
    echo json_encode(['status' => 'error', 'message' => $error_passed[0]]);
    exit;
}

//--------------------------- QUERY -------------------------//
// Build query with optional list_id filter
$query = '
    SELECT s.id, s.name, s.email, s.timestamp, s.custom_fields, s.country, s.unsubscribed, s.bounced, s.complaint, s.confirmed, 
           l.name as list_name, l.id as list_id
    FROM subscribers s
    JOIN lists l ON s.list = l.id  
    WHERE l.app = ? AND s.confirmed = 1 AND s.unsubscribed = 0';
$params = [$brand_id];
$types = 'i';
if ($list_id) {
    $query .= ' AND l.id = ?';
    $params[] = $list_id;
    $types .= 's';
}
$query .= ' ORDER BY s.timestamp DESC LIMIT ? OFFSET ?';
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $mysqli->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$subscribers = [];
while ($row = $result->fetch_assoc()) {
    // Engagement metrics: opens, clicks, campaigns_received, engagement_score
    $subscriber_id = $row['id'];
    $campaigns_received = 0;
    $total_opens = 0;
    $total_clicks = 0;
    $engagement_score = 0;
    
    // Get campaigns for this brand
    $campaigns_stmt = $mysqli->prepare('SELECT id, opens FROM campaigns WHERE app = ?');
    $campaigns_stmt->bind_param('i', $brand_id);
    $campaigns_stmt->execute();
    $campaigns_result = $campaigns_stmt->get_result();
    while ($camp = $campaigns_result->fetch_assoc()) {
        $campaigns_received++;
        // Opens
        if (!empty($camp['opens']) && in_array($subscriber_id, explode(',', $camp['opens']))) {
            $total_opens++;
        }
        // Clicks
        $links_stmt = $mysqli->prepare('SELECT clicks FROM links WHERE campaign_id = ?');
        $links_stmt->bind_param('i', $camp['id']);
        $links_stmt->execute();
        $links_result = $links_stmt->get_result();
        while ($link = $links_result->fetch_assoc()) {
            if (!empty($link['clicks']) && in_array($subscriber_id, explode(',', $link['clicks']))) {
                $total_clicks++;
            }
        }
        $links_stmt->close();
    }
    $campaigns_stmt->close();
    $engagement_score = $total_opens + ($total_clicks * 2);
    
    // Custom fields
    $custom_fields = [];
    if (!empty($row['custom_fields'])) {
        $fields = explode(',', $row['custom_fields']);
        foreach ($fields as $field) {
            $kv = explode(':', $field, 2);
            if (count($kv) == 2) {
                $custom_fields[trim($kv[0])] = trim($kv[1]);
            }
        }
    }
    
    $subscribers[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'email' => $row['email'],
        'signup_date' => date('Y-m-d', $row['timestamp']),
        'list_name' => $row['list_name'],
        'list_id' => $row['list_id'],
        'total_opens' => $total_opens,
        'total_clicks' => $total_clicks,
        'campaigns_received' => $campaigns_received,
        'engagement_score' => $engagement_score,
        'country' => $row['country'],
        'status' => ($row['confirmed'] && !$row['unsubscribed'] && !$row['bounced'] && !$row['complaint']) ? 'active' : 'inactive',
        'custom_fields' => $custom_fields
    ];
}

$total_count = count($subscribers);
echo json_encode([
    'status' => 'success',
    'total_count' => $total_count,
    'subscribers' => $subscribers
], JSON_PRETTY_PRINT);
?>
