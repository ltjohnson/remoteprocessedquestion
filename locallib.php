<?php
/**
 * Library routines used by the ltjprocessed question type
 *
 * @copyright &copy; 2007 Leif Johnson
 * @author leif.t.johnson@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ltjprocessed
 */

/*************************************************************
 * These functions are just to declare table names in a semi-intelligent way
 *
 */
function ltj_tbl()          { return 'question_ltjprocessed'; }
function ltj_ans_tbl()      { return 'question_ltjprocessed_answers'; }
function ltj_serv_tbl()     { return 'question_ltjprocessed_servers'; }
function ans_tbl()          { return 'question_answers'; }

/*************************************************************
 * functions to support file uploads
 */
/* returns the file area name for this attempt and question */
function question_file_area_name($attemptid, $questionid) {
  global $CFG, $USER;
  
  if ($attemptid == 0) {
    $attemptid = $attemptid."_".$USER->id;
  }
  
  return 'questionattempt/'.$attemptid.'/'.$questionid;
}

function question_file_area($dir) {
  return make_upload_directory($dir);
}
/*************************************************************/
function ltj_add_elements(&$arr, $arrid, $src, $srcid) {
  while(count($arr) < count($src[$srcid])) {
    array_push($arr, new stdClass);
  }
  for($i=0; $i<count($src[$srcid]); $i++) {
    $arr[$i]->$arrid = urldecode($src[$srcid][$i]);
  }
}
/*************************************************************/
/* this is a function to match up a imported/restored server reference with
 * an existing one if possible, and to make a new one if necessary, altering the
 * name with the prestring to make it obvious.
 * It returns a non-zero server id on success.  This server id does exist
 * in the database.  The url associated with it *will* match the one passed in
 * in $server->serverurl
 */
function restore_server_record($server, $prestring="RESTORED") {
  $servers = get_records(ltj_serv_tbl(), 'serverurl', $server->serverurl,
			 'id');
  $servermatch = false;
  if ($servers && (count($servers) > 0)) {
    foreach($servers as $srv) {
      if ($srv->servername == $server->servername) {
	$servermatch = true;
	$server->id = $srv->id;
      }
    }
    if (!$servermatch) {
      // we have matching urls, but no name matches
      // let's just grab the first one, cause it's the same url
      $server->id = $servers[0]->id;
      $server->servername = $servers[0]->servername;
      $servermatch = true;
    }
  }
  if ($servermatch==false) {
    // make it obvious that there was a conflict in finding a suitable 
    // server by adjusting the name
    $server->servername .= " $prestring ". date("Ymd");
    $server->id = insert_record(ltj_serv_tbl(), $server);
  }
  
  return $server->id;
}
/*************************************************************
 * lists installed servers in a menu form for edit_ltjprocessed
 */
function installed_server_choices() {
  return get_records_menu(ltj_serv_tbl(), '', '', 'servername ASC', 
			  'id, servername');
}

/*************************************************************/
/* helper function for sorting */
function cmp_id($a, $b) {
  if ($a->id == $b->id) {
    return 0;
  }
  return ($a->id < $b->id) ? -1 : 1;
}

/************************************************************************
 *  functions for xmlrpc client
 */
/*  function do_rpc_call
 *  $url     -- url of rpc server
 *  $request -- request to send to server
 *  handles sending the body of a request to a server and getting the response
 *  returns the xml from the server
 */
function do_rpc_call($url, $request) {
  $header[] = "Content-type: text.xml";
  $header[] = "Content-length: ".strlen($request);

  $crl = curl_init();
  curl_setopt($crl, CURLOPT_URL, $url);
  curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($crl, CURLOPT_TIMEOUT, 10);
  curl_setopt($crl, CURLOPT_HTTPHEADER, $header);
  curl_setopt($crl, CURLOPT_POSTFIELDS, $request);

  $data = curl_exec($crl);
  $response = new stdClass;
  $response->success = true;
  $response->data    = NULL;
  $response->warning = NULL;
  if (curl_errno($crl)) {
    $response->success = false;
    $response->warning = "RECEIVED curl_errno: ". curl_errno($crl) .": ". curl_error($crl);
  } else {
    $response->data = substr($data, strpos($data, "<methodResponse>"));
  }
  curl_close($crl);
  return $response;
}
/*
 *  function xmlrpc_request
 *  $server -- xml rpc server
 *  $method -- method to request form the server
 *  $args   -- php args to send in the request
 *  this function handles all the details of an xmlrpc request to a remote 
 *  server, returning a php class containing three variables
 *  'success' true or false for success of request
 *  'warning' a string containing a warning if the request was not successful
 *  'data' a php version of the response if the request was successful
 */
function xmlrpc_request($server, $method, $args) {
  $request  = xmlrpc_encode_request($method, $args);
  $response = do_rpc_call($server, $request);
  if ($response->success) {
    $response->data  = xmlrpc_decode($response->data);
    // Not quite done looking for errors yet, we need to see if 
    // a faultcode was sent back
    if (array_key_exists('faultCode', $response->data)) {
      $response->success = false;
      $response->warning = "Error processing question: </br>".
	"faultCode[". $response->data['faultCode'] . "] </br>".
	"faultString[". $response->data['faultString'] . "] </br>";
      $response->data = NULL;
    }
  }
  return $response;
}
