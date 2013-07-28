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
 * Defines the editing form for the remoteprocessed question type.
 *
 * @package    qtype
 * @subpackage remoteprocessed
 * @copyright  2013 Leif Johnson (leif.t.johnson@gmail.com)

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * remoteprocessed question editing form definition.
 *
 * @copyright  2013 Leif Johnson (leif.t.johnson@gmail.com)

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_remoteprocessed_edit_form extends question_edit_form {

    protected function definition_inner($mform) {
        $this->add_interactive_settings();
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_hints($question);

        return $question;
    }

    public function qtype() {
        return 'remoteprocessed';
    }
    
    /**
     * Add question-type specific form fields.
     *
     * @param MoodleQuickForm $mform, the form being built.
     */
    protected function definition_inner($mform) {
      // adds the elements specific to this question type to the form used
      // for editing question data.  This is what an instructor sees when 
      // editing/creating questions of this type.
      
      // Add the variables and image code above the question text.  
      // Variables goes first, then imagecode, then questiontext, as this is 
      // the order the code will be evaluated in.
      $mform->insertElementBefore(
        $mform->createElement('textarea', 'imagecode', '', 
			      array('rows' => 5, 'cols' => 80)),
	'questiontext');
      $mform->insertElementBefore(
        $mform->createElement('textarea', 'variables', '',
			      array('rows' =>15, 'cols' => 80)),
	'imagecode');
      
      // TODO(leif): Add server selection dialog.
				  
    }
}
