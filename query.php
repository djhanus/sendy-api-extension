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


    //-------------------------- ERRORS -------------------------//
	$error_core = array('No data passed', 'API key not passed', 'Invalid API key');
	$error_passed = array(
	  'Brand ID not passed',
      'Query not passed',
      'This search yielded no results'
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

    // Convert date_sent to Unix timestamp if it's in another format
    if ($date_sent) {
        if (is_numeric($date_sent)) {
            // Assume it's a Unix timestamp
            $date_sent_unix = (int)$date_sent;
        } else { 
            // Assume it's in another format
            $date_sent_unix = strtotime($date_sent); //strtotime works for many date formats
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
      $q .= ' AND sent >= '.$date_sent_unix;
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
                $link_data['clicks'] = $link_data['clicked'];
                unset($link_data['clicked']);
                
            // Sum up total clicks
        $total_clicks += $link_data['clicks'];
        $click_rate = round(($link_data['clicks'] / $data['total_sent']) * 100, 2);
        $links[] = $link_data;
    }
    $data['total_clicks'] = $total_clicks;
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