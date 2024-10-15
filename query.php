<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');

include('../_connect.php');
include('../../includes/helpers/short.php');

header('Content-Type: application/json; charset=utf-8');
?>
<?php 
/*
---Little helper function for reporting

Put this file in a new folder within the /api/ folder, called "reporting", and call it "query.php". (Or whatever you like).

Call by POST to api/reporting/query.php with the following mandatory elements
  'api_key' => (your API key)
  'brand_id' => (the brand ID you want to search)
  'query' (optional) => Search within the campaign name/label. If not included all campaigns will be returned.
  'order' (optional) => sort by date sent 'asc' or 'desc' (default is 'desc')
  'sent' (optional) => filter by date sent. Can be a Unix timestamp or a date in M/d/YY format. If not included all campaigns will be returned.
  
  (Using the campaign name allows you to search for multiple campaigns without knowing its campaign ID)

The data return is in JSON and contains following:

brand_id: the brand ID you sent
id: the campaign ID
label: the campaign label/name
date_sent: the date the campaign was sent converted from Unix
total_sent: the total sent for this campaign
total_opens: the total opens figure, visible in your dashboard
open_rate: total opens as a percentage of total sent
unique_opens: de-duplicated opens figure
open_percentage: the percentage of unique opens against total sent
total_clicks: the total number of clicks on all links in the campaign
click_rate: the total clicks as a percentage of total sent
links: an array of links within the campaign, with the following elements:
  url: the URL of the link
  clicks: the number of clicks on the link


*/


//-------------------------- ERRORS -------------------------//
	$error_core = array('No data passed', 'API key not passed', 'Invalid API key');
	$error_passed = array(
	  'Brand ID not passed'
	, 'Query not passed'
    , 'This search yielded no results'
	);
	//-----------------------------------------------------------//

  //  
	
	//--------------------------- POST --------------------------//
	//api_key	
	if(isset($_POST['api_key']))
		$api_key = mysqli_real_escape_string($mysqli, $_POST['api_key']);
	else $api_key = null;
	
	//brand_id
	if(isset($_POST['brand_id']) && is_numeric($_POST['brand_id']))
		$brand_id = mysqli_real_escape_string($mysqli, $_POST['brand_id']);
	else $brand_id = null;
		
	//query
	if(isset($_POST['query']))
		$query = mysqli_real_escape_string($mysqli, $_POST['query']);
	else $query = null;

    //date sent
    if(isset($_POST['date_sent']))
    $date_sent = mysqli_real_escape_string($mysqli, $_POST['date_sent']);
    else $date_sent = null;
    
    //order by
    if(($_POST['order']=='asc') || ($_POST['order']=='desc'))
    $order = mysqli_real_escape_string($mysqli, $_POST['order']);
    else $order = 'desc';

	//-----------------------------------------------------------//
	
	//----------------------- VERIFICATION ----------------------//
	//Core data
	if($api_key==null && $brand_id==null && $query==null)
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
	
	//Passed data
	if($brand_id==null)
	{
		echo $error_passed[0];
		exit;
	}

    // Convert date_sent to Unix timestamp if it's in M/d/YY format
    if ($date_sent) {
        if (is_numeric($date_sent)) {
            // Assume it's a Unix timestamp
            $date_sent_unix = (int)$date_sent;
        } else {
            // Assume it's in M/d/YY format
            $date_sent_unix = strtotime($date_sent);
            if ($date_sent_unix === false) {
                echo json_encode(['error' => 'Invalid date format']);
                exit;
            }
        }
    }

  $q = 'SELECT id, to_send, opens, label, sent FROM campaigns WHERE app = '.$brand_id;

  if ($query !== null) {
      $q .= ' AND label LIKE "%'.$query.'%"';
  }
  if ($date_sent !== null) {
      $q .= ' AND sent >= '.$date_sent;
  }
  
  $q .= ' ORDER BY sent '.$order.';';

  $r = mysqli_query($mysqli, $q);

  if ($r === false) {
      // Log the error message
      error_log('MySQL query error: ' . mysqli_error($mysqli));
      // Return an appropriate response
      http_response_code(500);
      echo json_encode(['error' => 'Database query failed']);
      exit;
  }

  if (mysqli_num_rows($r) == 0) 
  {
    echo $error_passed[2]; 
    exit;
  }
  else
  {
    $campaigns = [];
    while ($data = mysqli_fetch_assoc($r)) {
        $data['brand_id'] = $brand_id;
        $campaign_id = $data['id'];
        $data['label'] = $data['label'];
        $data['date_sent'] = date('l, F j, Y g:i:s A', $data['sent']);
        $data['total_sent'] = $data['to_send'];
        $opens = stripslashes($data['opens']);
        $opens_array = explode(',', $opens);
        $data['total_opens'] = count($opens_array);
        $data['open_rate'] = round(($data['total_opens'] / $data['total_sent']) * 100, 2);

        $data_opens = array();
        $data_country = array(); // Initialize the array

        foreach ($opens_array as $open) {
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

        $data['unique_opens'] = count($data_opens);
        $data['open_percentage'] = round(($data['unique_opens'] / $data['total_sent']) * 100, 2);

        // Fetch link data for the current campaign
        $link_query = 'SELECT LEFT(REPLACE(link,query_string,""),CHAR_LENGTH(REPLACE(link,query_string,"")) -1) AS url, IF(CHAR_LENGTH(clicks),1+(CHAR_LENGTH(clicks) - CHAR_LENGTH(REPLACE(clicks, ",", ""))),0) AS clicked FROM links JOIN campaigns ON campaigns.id=links.campaign_id WHERE app = '.$brand_id.' AND campaign_id = "'.$data['id'].'";';
        $link_result = mysqli_query($mysqli, $link_query);
        
        // clean up link data
        
        if ($link_result !== false) {
            $links = [];
            while ($link_data = mysqli_fetch_assoc($link_result)) {
                // Rename "clicked" to "clicks"
                $link_data['clicks'] = $link_data['clicked'];
                unset($link_data['clicked']);
                
            // Sum up total clicks
        $total_clicks += $link_data['clicks'];
        $click_rate = round(($link_data['clicks'] / $data['total_sent']) * 100, 2);
        $links[] = $link_data;
    }
    $data['total_clicks'] = $total_clicks; // Add total clicks above the links array
    $data['links'] = $links;
        }

        // Tidy up the data a little
        unset($data['to_send']);
        unset($data['opens']);
        unset($data['sent']);

        $campaigns[] = $data;
    }

    // Return the reports as a JSON response
    echo json_encode(['campaigns' => $campaigns], JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
    exit;
  }
	//-----------------------------------------------------------//
?>