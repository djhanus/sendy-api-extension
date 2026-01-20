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

// Get the true total count of matching subscribers (without LIMIT/OFFSET)
$count_query = '
    SELECT COUNT(*) as total
    FROM subscribers s
    JOIN lists l ON s.list = l.id
    WHERE l.app = ? AND s.confirmed = 1 AND s.unsubscribed = 0';
$count_params = [$brand_id];
$count_types = 'i';
if ($list_id) {
    $count_query .= ' AND l.id = ?';
    $count_params[] = $list_id;
    $count_types .= 's';
}
$count_stmt = $mysqli->prepare($count_query);
$count_stmt->bind_param($count_types, ...$count_params);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_count = 0;
if ($row_count = $count_result->fetch_assoc()) {
    $total_count = (int)$row_count['total'];
}
$count_stmt->close();

// Pre-fetch all campaigns for this brand
$campaigns = [];
$campaign_ids = [];
$campaigns_query = $mysqli->prepare('SELECT id, opens FROM campaigns WHERE app = ?');
$campaigns_query->bind_param('i', $brand_id);
$campaigns_query->execute();
$campaigns_result = $campaigns_query->get_result();
while ($camp = $campaigns_result->fetch_assoc()) {
    $campaigns[$camp['id']] = $camp;
    $campaign_ids[] = $camp['id'];
}
$campaigns_query->close();

// Pre-fetch all links for these campaigns using prepared statements
$links_by_campaign = [];
if (!empty($campaign_ids)) {
    // Create placeholders for IN clause
    $placeholders = implode(',', array_fill(0, count($campaign_ids), '?'));
    $links_sql = "SELECT campaign_id, clicks FROM links WHERE campaign_id IN ($placeholders)";
    $links_stmt = $mysqli->prepare($links_sql);
    
    // Bind parameters dynamically
    $types = str_repeat('i', count($campaign_ids));
    $links_stmt->bind_param($types, ...$campaign_ids);
    $links_stmt->execute();
    $links_result = $links_stmt->get_result();
    
    while ($link = $links_result->fetch_assoc()) {
        if (!isset($links_by_campaign[$link['campaign_id']])) {
            $links_by_campaign[$link['campaign_id']] = [];
        }
        $links_by_campaign[$link['campaign_id']][] = $link;
    }
    $links_stmt->close();
}

$stmt = $mysqli->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$subscribers = [];
while ($row = $result->fetch_assoc()) {
    // Engagement metrics: opens, clicks, campaigns_received, engagement_score
    $subscriber_id = $row['id'];
    $total_opens = 0;
    $total_clicks = 0;
    $campaigns_engaged = 0; // Count campaigns this subscriber actually engaged with
    $engagement_score = 0;
    
    // Calculate opens and clicks from pre-fetched campaigns and links
    foreach ($campaigns as $camp_id => $camp) {
        $engaged_with_campaign = false;
        
        // Opens - use FIND_IN_SET equivalent check with strict comparison
        if (!empty($camp['opens'])) {
            $opens_array = explode(',', $camp['opens']);
            if (in_array((string)$subscriber_id, $opens_array, true)) {
                $total_opens++;
                $engaged_with_campaign = true;
            }
        }
        // Clicks
        if (isset($links_by_campaign[$camp_id])) {
            foreach ($links_by_campaign[$camp_id] as $link) {
                if (!empty($link['clicks'])) {
                    $clicks_array = explode(',', $link['clicks']);
                    if (in_array((string)$subscriber_id, $clicks_array, true)) {
                        $total_clicks++;
                        $engaged_with_campaign = true;
                    }
                }
            }
        }
        
        // Count this campaign if subscriber engaged with it
        if ($engaged_with_campaign) {
            $campaigns_engaged++;
        }
    }
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
        'campaigns_engaged' => $campaigns_engaged,
        'engagement_score' => $engagement_score,
        'country' => $row['country'],
        'status' => ($row['confirmed'] && !$row['unsubscribed'] && !$row['bounced'] && !$row['complaint']) ? 'active' : 'inactive',
        'custom_fields' => $custom_fields
    ];
}

echo json_encode([
    'status' => 'success',
    'total_count' => $total_count,
    'page_count' => count($subscribers),
    'subscribers' => $subscribers
], JSON_PRETTY_PRINT);
?>
