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
 * Server config page for the Remote Processed question type.
 *
 * @package    qtype
 * @subpackage remoteprocessed
 * @copyright  2013 Leif Johnson (leif.t.johnson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(dirname(__FILE__) . '/serverlib.php');

// Check the user is logged in.
require_login();
$context = context_system::instance();
require_capability('moodle/question:config', $context);

admin_externalpage_setup('qtypesettingremoteprocessed');

$servers_url    = '/question/type/remoteprocessed/servers.php';
$editserver_url = '/question/type/remoteprocessed/editserver.php';
$testserver_url = '/question/type/remoteprocessed/testserver.php';

$serverid = optional_param('serverid', 0, PARAM_INT);

if (!$serverid) {
  // Exit if we don't have a serverid to work on
  print_error('testserver_needserverid', 'qtype_remoteprocessed', 
              $CFG->wwwroot . $servers_url);
  die();
}

$server = qtype_remoteprocessed_server_helper::load_server_by_id($serverid);
if (!$server) {
  print_error('testserver_unknownserver', 'qtype_remoteprocessed',
              $CFG->wwwroot . $servers_url);
  die();
}

// Start page output.
echo $OUTPUT->header();
echo $OUTPUT->heading_with_help(get_string('testserver_testserver',
                                          'qtype_remoteprocessed'),
                                'testserver_testserver',
                                'qtype_remoteprocessed');

$response = qtype_remoteprocessed_server_helper::server_status_call($server);

$table = array();

if ($response->success == false) {
  echo '<div class="errorbox">'. $response->warning. '</div>';
} else {
  /* print table of results */
  foreach($response->data as $key => $value) {
    array_push($table, 
               array('key' => $key, 'value' => $value));
  }
}

// TODO: Convert this to using an actual HTML table.
$start_p = html_writer::start_tag('p');
$end_p = html_writer::end_tag('p');
$seperator_string = html_writer::tag('b', format_string(':'));
foreach ($table as $table_row) {
  echo $start_p;
  echo ' ' , $table_row['key'];
  echo ' ' . $seperator_string;
  echo ' ' . $table_row['value'];
  echo $end_p;
}

echo $OUTPUT->footer();