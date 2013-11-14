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
 * Remote processed server management/editing helper functions.
 *
 * @package    qtype
 * @subpackage remoteprocessed
 * @copyright  2013 Leif Johnson (leif.t.johnson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/locallib.php');

class qtype_remoteprocessed_server_helper {
  public static function load_server_by_id($serverid) {
  	global $DB;

  	return $DB->get_record('question_rmtproc_servers', 
  		                   array('id' => $serverid));
  }

  public static function servers() {
  	global $DB;

  	return $DB->get_records_sql("SELECT * FROM {question_rmtproc_servers}");
  }

  public static function server_counts() {
  	global $DB;

  	return $DB->get_records_sql_menu(
  		'SELECT serverid, COUNT(1) FROM {question_rmtproc} GROUP BY serverid');
  }

  /* 
   * Get a menu of available remote processing servers.
   */
  public static function get_remote_processed_servers_menu() {
    global $DB;
    $menu = $DB->get_records_menu('question_rmtproc_servers',
	                              null,
	                              'id ASC',
	                              'id,name');
      return $menu;
    }

  public static function get_server_form_values($serverid) {
    $server = 
      qtype_remoteprocessed_server_helper::get_server_values($serverid);
    $server_ret = new stdClass;
    $server_ret->serverid   = $server->id;
    $server_ret->servername = $server->name;
    $server_ret->serverurl  = $server->url;

    return $server_ret;
  }

  public static function get_server_values($serverid) {
    // If serverid is passed, get the values from the DB, otherwise return
    // default values.
    global $DB;

    if (isset($serverid) && $serverid > 0) {
      $server = $DB->get_record('question_rmtproc_servers',
				array('id' => $serverid));
    }

    if (!isset($server)) {
      $server = (object) array('name' => '', 'url' => '', 'id' => 0);
    }

    return $server;
  }

  public static function delete($serverid) {
    global $DB;

    error_log("Deleting server $serverid");

    $DB->delete_records('question_rmtproc_servers', array('id' => $serverid));
  }

  public static function save_server($server) {
  	global $DB;
 
  	if (isset($server->id)) {
  		$DB->update_record('question_rmtproc_servers',
  			               $server);
  	} else {
      $server-> id = $DB->insert_record('question_rmtproc_servers',
  			                             $server);
  	}
  }

  public static function find_or_insert_server_by_url($id, $name, $url) {
    global $DB;

    // See if there is an existing record matching this url, if so, return that.
    $server = $DB->get_record("question_rmtproc_servers",
      array('url' => $url));
    if ($server) {
      return $server;
    }

    $server = (object) array("name" => "IMPORTED " . $name, 
                             "url"  => $url);
    $server->id = $DB->insert_record("question_rmtproc_servers", $server);

    return $server;
  }

  public static function status_xml_request_args($server) {
    $request = new stdClass;
    $request->method = 'status';
    $request->args   = array();
    $request->server = $server->url;
    return $request;
  }

  public static function server_status_call($server) {
    $request_args = qtype_remoteprocessed_server_helper::status_xml_request_args($server);
    $response = xmlrpc_request($request_args);
    return $response;
  }
}