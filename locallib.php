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
function ltj_state_tbl()    { return 'question_ltjprocessed_states'; }
function ltj_ansstate_tbl() { return 'question_ltjprocessed_ans_states'; }
function ans_tbl()          { return 'question_answers'; }

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
function installed_server_choices() {
  return get_records_menu(ltj_serv_tbl(), '', '', 'servername ASC', 'id, servername');
  }

function get_post_url_contents($url, $urlvars) {
  $crl = curl_init();
  curl_setopt($crl, CURLOPT_URL, $url);
  curl_setopt($crl, CURLOPT_HEADER, 0);
  curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($crl, CURLOPT_POST, 1);
  curl_setopt($crl, CURLOPT_POSTFIELDS, $urlvars);
  $ret = curl_exec($crl);
  curl_close($crl);
  return $ret;
}

function ltj_implode($arr) {
  $ret = array();
  ksort($arr);
  foreach($arr as $key => $value) {
    $ret[] = $key . '-' . str_replace(',', '\,', $value);
  }
  return implode(',', $ret);
}

function ltj_explode($str) {
  if ($str == '') {
    return array();
  }
  // split string on non-backslashed commas.
  $sp = preg_split('/(?<!\\\\)\,/', $str);
  $arr = array();
  foreach($sp as $sc) {
    list($key, $val) = explode('-', $sc, 2);
    $arr[$key] = str_replace('\,', ',', $val);
  }
  return $arr;
}

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
	if (curl_errno($crl)) {
		echo "RECEIVED curl_errno: ". curl_errno($crl) .": ". curl_error($crl)."</br>";
		curl_close($crl);
	} else {
		curl_close($crl);
		$xml = substr($data, strpos($data, "<methodResponse>"));
		return $xml;
	}
}
/*
 *  function xmlrpc_request
 *  $server -- xml rpc server
 *  $method -- method to request form the server
 *  $args   -- php args to send in the request
 *  this function handles all the details of an xmlrpc request to a remote 
 *  server, returning a php version of the response
 */
function xmlrpc_request($server, $method, $args) {
	$request = xmlrpc_encode_request($method, $args);
	$xml     = do_rpc_call($server, $request);
	$phpvars = xmlrpc_decode($xml);
	return $phpvars;
}
