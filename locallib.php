<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * remoteprocessed question definition class.
 *
 * @package    qtype
 * @subpackage remoteprocessed
 * @copyright  2013 Leif Johnson (leif.t.johnson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * Helper functions for remote processed questions.
 *
 * @copyright  2013 Leif Johnson (leif.t.johnson@gmail.com)

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
 *  $request_args -- XmlRpc request args.  contains:
 *    * server -- xml rpc server
 *    * method -- method to request form the server
 *    * args   -- php args to send in the request
 *  This function handles all the details of an xmlrpc request to a remote 
 *  server, returning a php class containing three variables:
 *    * 'success' True or False for success of request.
 *    * 'warning' a string containing a warning if the request was not 
 *       successful.
 *    * 'data' a php version of the response if the request was successful.
 */
function xmlrpc_request($request_args) {
  $request  = xmlrpc_encode_request($request_args->method, $$equest_args->args);
  $response = do_rpc_call($request_args->server, $request);
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