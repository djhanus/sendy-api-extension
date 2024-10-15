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
---Little helper function from james@crid.land for reporting link-clicks in a newsletter.
Put this file in a new folder within the /api/ folder, called "reporting", and call this "links.php". (Or whatever you like).
Call by POST to api/reporting/reports.php with the following mandatory elements
  'api_key' => (your API key)
  'brand_id' => 1 or whatever
  'label' => (the campaign name)
  
  (Using the campaign name allows you to programmatically call a campaign without knowing its campaign ID)
The data return is in JSON, and contains the URL, total recipients, total opens, and the total clicks for this URL.

Use of the label allows you to programmatically query this without having to worry about looking up the campaign ID.

*/
//-------------------------- ERRORS -------------------------//
	$error_core = array('No data passed', 'API key not passed', 'Invalid API key');
	$error_passed = array(
	  'Brand ID not passed'
	, 'Label not passed'
  , 'This combination of Brand ID and Label does not exist'
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
		
	//label
	if(isset($_POST['label']))
		$label = mysqli_real_escape_string($mysqli, $_POST['label']);
	else $label = null;
	
	//----------------------- VERIFICATION ----------------------//
	//Core data
	if($api_key==null && $brand_id==null && $label==null)
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
	else if($label==null)
	{
		echo $error_passed[1];
		exit;
	}
  //So, here we are, I think.
  //We've been passed a brandID and a label.
  // $app = trim(short($app,true));
  $q = 'SELECT LEFT(REPLACE(link,query_string,""),CHAR_LENGTH(REPLACE(link,query_string,"")) -1) AS url,recipients,IF(CHAR_LENGTH(opens),1+(CHAR_LENGTH(opens) - CHAR_LENGTH( REPLACE ( opens, ",", "") )),0) AS opens, IF(CHAR_LENGTH(clicks),1+(CHAR_LENGTH(clicks) - CHAR_LENGTH( REPLACE ( clicks, ",", "") )),0) AS clicked FROM links JOIN campaigns ON campaigns.id=links.campaign_id WHERE app = '.$brand_id.' AND label = "'.$label.'";';
  $r = mysqli_query($mysqli, $q);
$output=array();
if (mysqli_num_rows($r) == 0) 
	{
		echo $error_passed[2]; 
		exit;
	}
		else
    {

      while ($data = mysqli_fetch_assoc($r))
       {
          $output[]=$data;
        }
      echo json_encode($output);
        
    }
	//-----------------------------------------------------------//
?>
