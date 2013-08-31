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

// We can't die if MOODLE_INTERNAL is not defined, because it isn't.  We're 
// running as an add on page here, we have to do the bookeeping and 
// initialization for ourselves.
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

// See if any action was requested.
$delete = optional_param('delete', 0, PARAM_INT);
if ($delete) {
	// User asked to delete a server.
    $server = qtype_remoteprocessed_server_helper::get_server_values($delete);

    // Make sure there are no questions using this server.
    if ($DB->record_exists('question_rmtproc', 
                           array('serverid' => $delete))) {
        print_error('editserver_servercantbedeleted', 'qtype_remoteprocessed',
                    $CFG->wwwroot . $servers_url);
    }

	if (optional_param('confirm', false, PARAM_BOOL) && confirm_sesskey()) {
        qtype_remoteprocessed_server_helper::delete($delete);
        redirect($PAGE->url);
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(get_string('editserver_deleteconf', 
                                         'qtype_remoteprocessed',
                                         format_string($server->name)),
                              new moodle_url($servers_url,
                                             array('delete' => $delete, 
                                                   'confirm' => 'yes', 
                                                   'sesskey' => sesskey())),
                              $PAGE->url);
        echo $OUTPUT->footer();
        die();
    }
}

// Get the list of configured servers.
$servers = qtype_remoteprocessed_server_helper::servers();
$server_counts = qtype_remoteprocessed_server_helper::server_counts();

// Header.
echo $OUTPUT->header();
echo $OUTPUT->heading_with_help(get_string('editserver_configured_servers', 
	                                       'qtype_remoteprocessed'),
                                'editserver_configured_servers', 
                                'qtype_remoteprocessed');

// List of configured engines.
if ($servers) {
    $strtest = get_string('editserver_testserver', 'qtype_remoteprocessed');
    $stredit = get_string('edit');
    $strdelete = get_string('delete');

    foreach ($servers as $server) {
        echo html_writer::start_tag('p');

        // Number of questions by server.
        $count = 0;
        if (!empty($server_counts[$server->id])) {
        	$count = $server_counts[$server->id];
        }

        $formated_server_name = 
          html_writer::tag('b', format_string($server->name));
        if ($count) {
        	echo get_string('editserver_servername_usagecount', 'qtype_remoteprocessed',
        		array('name' => $formated_server_name,
        		      'count' => html_writer::tag('b', $count)));
        } else {
        	echo get_string('editserver_servername_unused', 'qtype_remoteprocessed',
        		array('name' => $formated_server_name));
        }
        
        echo ' ' , $OUTPUT->action_icon(new moodle_url($testserver_url,
                        array('serverid' => $server->id)),
                        new pix_icon('t/preview', $strtest));

        echo ' ' , $OUTPUT->action_icon(new moodle_url($editserver_url,
                        array('serverid' => $server->id)),
                        new pix_icon('t/edit', $stredit));

        if (empty($server_counts[$server->id])) {
            echo ' ' , $OUTPUT->action_icon(new moodle_url($servers_url,
                            array('delete' => $server->id)),
                            new pix_icon('t/delete', $strdelete));
        }
        echo html_writer::end_tag('p');
    }
} else {
    echo html_writer::tag('p', get_string('editserver_noservers', 
    	'qtype_remoteprocessed'));
}

// Add new engine link.
echo html_writer::tag('p', html_writer::link(new moodle_url($editserver_url),
        get_string('editserver_addserver', 'qtype_remoteprocessed')));

// Footer.
echo $OUTPUT->footer();