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

require_once($CFG->dirroot . '/question/type/edit_question_form.php');
require_once($CFG->dirroot . '/question/type/remoteprocessed/questiontype.php');


/**
 * remoteprocessed question editing form definition.
 *
 * @copyright  2013 Leif Johnson (leif.t.johnson@gmail.com)

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_remoteprocessed_edit_form extends question_edit_form {

    protected function definition_inner($mform) {
      // Add the variables and image code above the question text.  
      // Variables goes first, then imagecode, then questiontext, as this is 
      // the order the code will be evaluated in.
      $mform->insertElementBefore(
        $mform->createElement('textarea', 'imagecode', 
			      get_string('imagecode', 'qtype_remoteprocessed'),
			      array('rows' => 5, 'cols' => 80)),
	'questiontext');
      $mform->insertElementBefore(
        $mform->createElement('textarea', 'variables', 
			      get_string('variablecode', 'qtype_remoteprocessed'),
			      array('rows' =>15, 'cols' => 80)),
	'imagecode');
      
      $mform->insertElementBefore(
        $mform->createElement('select', 'serverid', 
            get_string('server', 'qtype_remoteprocessed'),
            qtype_remoteprocessed::get_remote_processed_servers_menu()),
	  'variables');

      $mform->addElement(
        $mform->createElement('advcheckbox', 'remotegrade', 
          get_string('remotegrade', 'qtype_remoteprocessed')));
      $mform->addHelpButton('remotegrade', 'remotegrade', 'qtype_remoteprocessed');
      $mform->setDefault('remotegrade', 0);


      $this->add_per_answer_fields($mform, 
        get_string('answerno', 'qtype_remoteprocessed', '{no}'),
        question_bank::fraction_options());
      

      $this->add_interactive_settings();
    }

    protected function get_per_answer_fields($mform, $label, $gradeoptions,
            &$repeatedoptions, &$answersoption) {
        $repeated = parent::get_per_answer_fields($mform, $label, $gradeoptions,
                $repeatedoptions, $answersoption);

        $tolerance = $mform->createElement('text', 'tolerance',
           get_string('answertolerance', 'qtype_remoteprocessed'), 
	   array('size' => 30));
        $repeatedoptions['tolerance']['type'] = PARAM_TEXT;
        $repeatedoptions['tolerance']['default'] = "0.0";
        $elements = $repeated[0]->getElements();
        $elements[0]->setSize(30);
        array_splice($elements, 1, 0, array($tolerance));
        $repeated[0]->setElements($elements);

        return $repeated;
    }

    protected function data_preprocessing($question) {
      $question = parent::data_preprocessing($question);
      $question = $this->data_preprocessing_answers($question);
      $question = $this->data_preprocessing_hints($question);
	
      foreach (qtype_remoteprocessed_question::$options_keys as $key) {
        if (isset($question->options->{$key})) {
          $question->{$key} = $question->options->{$key};
        }
      }

      return $question;
    }

    protected function data_preprocessing_answers($question, $withanswerfiles = false) {
      $question = parent::data_preprocessing_answers($question, $withanswerfiles);
      if (empty($question->options->answers)) {
        return $question;
      }

      $key = 0;
      foreach ($question->options->answers as $answer) {
	// See comment in the parent method about this hack.
	unset($this->_form->_defaultValues["tolerance[$key]"]);

	$question->tolerance[$key] = $answer->tolerance;
	$key++;
      }

      return $question;
    }

    public function qtype() {
        return 'remoteprocessed';
    }
}
