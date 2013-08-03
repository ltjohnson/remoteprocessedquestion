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


function get_remote_processed_servers() {
  GLOBAL $DB;
  $menu = $DB->get_records_menu('question_rmtproc_servers',
				null,
				'id ASC',
				'id,name');
  return $menu;
}

/**
 * remoteprocessed question editing form definition.
 *
 * @copyright  2013 Leif Johnson (leif.t.johnson@gmail.com)

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_remoteprocessed_edit_form extends question_edit_form {

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
	$question = $this->data_preprocessing_answers($question, true);
        $question = $this->data_preprocessing_hints($question);
	
	foreach (qtype_remoteprocessed_question::$options_keys as $key) {
	  if (isset($question->options->{$key})) {
	    $question->{$key} = $question->options->{$key};
	  }
	}
	
	print "<br/>data_preprocessing<br/>";
	print_r($question);
	print "<br/>";
	  

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

    public function get_per_answer_fields($mform, $label, $gradeoptions, 
					  &$repeatedoptions, &$answersoption) {
      print "<br/>get_per_answer_fields</br>";
      $repeated = parent::get_per_answer_fields($mform, $label, $gradeoptions, 
						$repeatedoptions, $answersoption);
      
      $tolerance = $mform->createElement('text', 'tolerance', 
	 get_string('answertolerance', 'qtype_remoteprocessed'),
	 array('size' => 30));
      $repeatedoptions['tolerance']['type'] = PARAM_TEXT;
      $repeatedoptions['tolerance']['default'] = "0.0";
      $elements = $repeated[0]->getElements();
      $elements[0]->setSize(15);
      array_splice($elements, 1, 0, array($tolerance));
      $repeated[0]->setElements($elements);
      
      print "<br/>end get_per_answer_fields</br>";
      return $repeated;
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
        $mform->createElement('textarea', 'imagecode', 'Image Code', 
			      array('rows' => 5, 'cols' => 80)),
	'questiontext');
      $mform->insertElementBefore(
        $mform->createElement('textarea', 'variables', 'Variable Code',
			      array('rows' =>15, 'cols' => 80)),
	'imagecode');
      
      $mform->insertElementBefore(
	$mform->createElement('select', 'serverid', 'Server', 
                              get_remote_processed_servers()),
	'variables');
			
      $this->add_per_answer_fields(
        $mform, get_string('answerhdr', 'qtype_remoteprocessed', '{no}'),
	question_bank::fraction_options_full());
    }
}
