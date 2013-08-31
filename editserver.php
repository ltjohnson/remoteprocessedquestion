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
 * Page for editing a server for the Remote Processed question type.
 *
 * @package    qtype
 * @subpackage remoteprocessed
 * @copyright  2013 Leif Johnson (leif.t.johnson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(dirname(__FILE__) . '/serverlib.php');
require_once(dirname(__FILE__) . '/edit_server_form.php');

$servers_url    = '/question/type/remoteprocessed/servers.php';
$editserver_url = '/question/type/remoteprocessed/editserver.php';
$testserver_url = '/question/type/remoteprocessed/testserver.php';

$serverid = optional_param('serverid', 0, PARAM_INT);

// Check the user is logged in.
require_login();
$context = context_system::instance();
require_capability('moodle/question:config', $context);

$servers_redirect_url = new moodle_url($servers_url);

admin_externalpage_setup('qtypesettingremoteprocessed', '', null,
        new moodle_url($editserver_url, array('serverid' => $serverid)));
$PAGE->set_title(get_string('editserver_title', 'qtype_remoteprocessed'));
$PAGE->navbar->add(get_string('editserver_navbar', 'qtype_remoteprocessed'));

// Create form object (contains the input fields and buttons).
$mform = new qtype_remoteprocessed_server_edit_form('editserver.php');

if ($mform->is_cancelled()) {
    // User issued "Cancel" on the webform, redirect to the main server page.
    redirect($servers_redirect_url);
} else if ($data = $mform->get_data()) {
    // User issued "Save" on the webform, save and redirect to the main server 
    // page.
    $server = new stdClass();
    if (!empty($data->serverid)) {
        $server->id = $data->serverid;
    }
    $server->name = $data->servername;
    $server->url  = $data->serverurl;

    qtype_remoteprocessed_server_helper::save_server($server);
    redirect($servers_redirect_url);
}

// Get form data (or defaults), and fill the data to the form.
$form_data = 
  qtype_remoteprocessed_server_helper::get_server_form_values($serverid);
$mform->set_data($form_data);

// Display the form.
echo $OUTPUT->header();
echo $OUTPUT->heading_with_help(get_string('editserver_title', 'qtype_remoteprocessed'),
        'editserver_title', 'qtype_remoteprocessed');
$mform->display();
echo $OUTPUT->footer();