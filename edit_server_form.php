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
 * Defines the server editing form for the remoteprocessed question type.
 *
 * @package    qtype
 * @subpackage remoteprocessed
 * @copyright  2013 Leif Johnson (leif.t.johnson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

//////////////////////////////////////////////////////////////////////////
// Class for editing a remote processed server.
//////////////////////////////////////////////////////////////////////////
class qtype_remoteprocessed_server_edit_form extends moodleform {

	protected function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'servername', 
        	               get_string('editserver_servername', 
        	               	          'qtype_remoteprocessed'));
        $mform->addRule('servername', 
        	            get_string('editserver_servername_missing', 
        	            	       'qtype_remoteprocessed'),
                        'required', null, 'client');
        $mform->setType('servername', PARAM_MULTILANG);

        $mform->addElement('text', 'serverurl',
        	               get_string('editserver_serverurl', 
                                      'qtype_remoteprocessed'));
        $mform->addRule('serverurl',
        	            get_string('editserver_serverurl_missing', 
        	            	       'qtype_remoteprocessed'),
        	            'required', null, 'client');
        $mform->setType('serverurl', PARAM_MULTILANG);

        $mform->addElement('hidden', 'serverid');
        $mform->setType('serverid', PARAM_INT);

        $this->add_action_buttons();
    }

}
