<?php
/**
 * This page lets admins configure Remote Processed question servers.
 *
 * @copyright &copy; 2007 Leif Johnson
 * @author Leif Johnson; leif.t.johnson@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ltjprocessed
 *//* **/

require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Check the user is logged in.
require_login();
if (!has_capability('moodle/question:config', get_context_instance(CONTEXT_SYSTEM, SITEID))) {
    print_error('restricteduser');
}

$strtest = get_string('ltj_testconnection', 'qtype_ltjprocessed');
$stredit = get_string('edit');
$strdelete = get_string('delete');

// See if any action was requested.
$delete = optional_param('delete', 0, PARAM_INT);
if ($delete) {
    $server = get_record('question_ltjprocessed_servers', 'id', $delete);
    if (is_string($server)) {
        print_error('ltj_unknownserver', 'qtype_ltjprocessed', 
		    'servers.php', $server);
    }
    if (optional_param('confirm', false, PARAM_BOOL) && confirm_sesskey()) {
      delete_records('question_ltjprocessed_servers', id, $server->id);
	redirect('servers.php');
    } else {
        notice_yesno(get_string('ltj_deleteconfigareyousure', 
				'qtype_ltjprocessed', $server->servername), 
		     'servers.php', 'servers.php',
                array('delete' => $delete, 'confirm' => 'yes', 'sesskey' => sesskey()), array(), 'post', 'get');
        exit;
    }
}

// Get the list of configured engines.
$servers = get_records('question_ltjprocessed_servers', '', '', 'id ASC');

// Header.
$strtitle = get_string('ltj_configuredservers', 'qtype_ltjprocessed');
print_header_simple($strtitle, '', $strtitle);
print_simple_box_start();
#print_heading_with_help($strtitle, 'qtype_opaque_configuredengines', 'quiz');

// List of configured engines.
if ($servers) {
    foreach ($servers as $server) {
        ?>
<p><?php p($server->servername) ?> 
<a title="<?php p($strtest) ?>" href="testserver.php?serverid=<?php echo $server->id ?>"><img
        src="<?php p($CFG->pixpath . '/t/preview.gif') ?>" border="0" alt="<?php p($strtest) ?>" /></a>
<a title="<?php p($stredit) ?>" href="editserver.php?id=<?php echo $server->id ?>"><img
        src="<?php p($CFG->pixpath . '/t/edit.gif') ?>" border="0" alt="<?php p($stredit) ?>" /></a>
<a title="<?php p($strdelete) ?>" href="servers.php?delete=<?php echo $server->id ?>"><img
        src="<?php p($CFG->pixpath . '/t/delete.gif') ?>" border="0" alt="<?php p($strdelete) ?>" /></a>
</p>
        <?php
        // TODO add a test icon that loads the engine metadata in a popup window.
    }
} else {
    echo '<p>', get_string('ltj_noservers', 'qtype_ltjprocessed'), '</p>';
}

// Add new engine link.
echo '<p class="mdl-align"><a href="editserver.php">', get_string('ltj_addserver', 'qtype_ltjprocessed'), '</a></p>';

// Footer.
print_simple_box_end();
print_footer();

?>
