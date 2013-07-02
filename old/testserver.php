<?php // $Id$
/**
 * Page for testing servers.
 *
 * @copyright &copy; 2008 University of Minnesota, Twin Cities
 * @author Leif Johnson; leif.t.johnson@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ltjprocessed
 *//** */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Check the user is logged in.
require_login();
if (!has_capability('mod/quiz:manage', 
		    get_context_instance(CONTEXT_SYSTEM, SITEID))) {
  print_error('restricteduser');
}

// Get the passed serverid
$serverid = optional_param('serverid', 0, PARAM_INT);
if (!$serverid) {
  // Exit if we don't have a serverid to work on
  print_error('ltj_needserverid', 'qtype_ltjprocessed', 'servers.php');
  exit;
}

/* acquire server record */
$server = get_record(ltj_serv_tbl(), 'id', $serverid);
if (!$server) {
  print_error('ltj_unkownserver', 'qtype_ltjprocessed', 'servers.php');
  exit;
}
/*****************************************************************/
/* print the title */
$strtitle = get_string('ltj_testserver', 'qtype_ltjprocessed');
print_header_simple($strtitle, '', $strtitle);
print_simple_box_start();


/* we store everything in a table and print it out at the end */
$table = new stdClass;
$table->head = array('Key', 'Value');
$table->data = array();
array_push($table->data, array("Server Name", $server->servername));
array_push($table->data, array("Server URL", $server->serverurl));

/* do status request */
$response = xmlrpc_request($server->serverurl, 'status', array());
if ($response->success == false) {
  echo '<div class="errorbox">'. $response->warning. '</div>';
  array_push($table->data, array("WARNING", $response->warning));
} else {
  /* print table of results */
  foreach($response->data as $key => $value) {
    array_push($table->data, array($key, $value));
  }
}

/* Print table, then close out page */
print_table($table);
print_simple_box_end();
print_footer();
?>
