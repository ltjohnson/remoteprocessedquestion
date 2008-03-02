<?php // $Id$
/**
 * Page for configuring the servers that Remote Processed questions can use
 *
 * @copyright &copy; 2007 Leif Johnson
 * @author Leif Johnson; leif.t.johnson@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ltjprocessed
 *//** */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');
include_once($CFG->libdir . '/validateurlsyntax.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Check the user is logged in.
require_login();
if (!has_capability('moodle/question:config', get_context_instance(CONTEXT_SYSTEM, SITEID))) {
    print_error('restricteduser');
}

/** Form definition class. */
class ltjprocessed_server_edit_form extends moodleform {
    /** Form definition. */
    function definition() {
        $mform =& $this->_form;
        $renderer =& $mform->defaultRenderer();
        
        $mform->addElement('text', 'servername', get_string('ltj_servername', 'qtype_ltjprocessed'));
        $mform->addRule('servername', get_string('ltj_missingservername', 'qtype_ltjprocessed'), 'required', null, 'client');
        $mform->setType('servername', PARAM_MULTILANG);

	$mform->addElement('text', 'serverurl', get_string('ltj_serverurl', 'qtype_ltjprocessed'));
	$mform->addRule('serverurl', get_string('ltj_missingserverurl', 'qtype_ltjprocessed'), 'required', null, 'client');
        $mform->setType('serverurl', PARAM_RAW);
		$mform->addElement('hidden', 'id');
		$mform->setType('id', PARAM_INT);
        
        $this->add_action_buttons();
    }
    
    /**
     * Validate the submitted data.
     * 
     * @param $data the submitted data.
     * @return true if valid, or an array of error messages if not.
     */
    function validation($data) {
        $errors = array();

        if ($errors) {
            return $errors;
        } else {
            return true;
        }
    }
}

$mform = new ltjprocessed_server_edit_form('editserver.php');

if ($mform->is_cancelled()){
    redirect('servers.php');
} else if ($data = $mform->get_data()){
    // Update the database.

    $server = new stdClass;
    $server->servername = $data->servername;
    $server->serverurl = $data->serverurl;

    if (!empty($data->id) && $data->id != 0) {
        $server->id = $data->id;
        update_record(ltj_serv_tbl(), $server);
    } else {
      $server->id = insert_record(ltj_serv_tbl(), $server);
    }
    
    // XXX TODO check for db errors
    redirect('servers.php');

} else {
    // Prepare defaults.
    $defaults = new stdClass;
    $id = optional_param('id', '0', PARAM_INT);
    $defaults->id = $id; 
    if ($id) {
      $server = get_record(ltj_serv_tbl(), 'id', $id);
      if (!$server) {
	// XXX ERROR here
      }
      $defaults->servername = $server->servername;
      $defaults->serverurl = $server->serverurl;
    }

    // Display the form.
    $strtitle = get_string('ltj_editserver', 'qtype_ltjprocessed');
    print_header_simple($strtitle, '', $strtitle);
    #print_heading_with_help($strtitle, 'qtype_opaque_editengine', 'qtype_opaque');
    $mform->set_data($defaults);
    $mform->display();
    print_footer();
}

?>
