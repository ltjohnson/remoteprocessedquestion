<?php
/**
 * The editing form code for this question type.
 *
 * @copyright &copy; 2007 Leif Johnson
 * @author leif.t.johnson@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ltjprocessed
 *//** */

require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->dirroot.'/question/type/edit_question_form.php');

/**
 * Remote Processed Question editing form definition.
 * 
 * See http://docs.moodle.org/en/Development:lib/formslib.php for information
 * about the Moodle forms library, which is based on the HTML Quickform PEAR library.
 */
class question_edit_ltjprocessed_form extends question_edit_form {
  function definition_inner(&$mform) {
    // TODO: The general formatting of the form needs work.  E.g. the 
    //       variables and server selection don't line up like they should

    // create these two elements, then insert them before the question text
    // Select server element
    $server_select = 
      $mform->createElement('select', 'serverid', 
			    get_string('ltj_server', 'qtype_ltjprocessed'), 
			    installed_server_choices());
    $mform->setType('serverid', PARAM_INT);

    // variables element 
    $lbl   = get_string('ltj_variables', 'qtype_ltjprocessed');
    $attrs = array('rows'      => 4, 
		   'cols'      => 60);
    $variable_element = 
      $mform->createElement('textarea', 'variables', $lbl, $attrs);
    $mform->setType('variables', PARAM_RAW);

    $imagecode_element = 
      $mform->createElement('textarea', 'imagecode', "Image Code", $attrs);
    $mform->setType('imagecode', PARAM_RAW);

    // insert them *before* questiontext
    $mform->insertElementBefore($imagecode_element, 'questiontext');
    $mform->insertElementBefore($variable_element, 'imagecode');
    $mform->insertElementBefore($server_select, 'variables');
    $mform->addRule('serverid', null, 'required', null, 'client' );
    ///////////////////////////////////////////////////////////////
	// This goes at the bottom by the answers, but it's not a repeated
	// element cause it's a question level option
    $mform->addElement('checkbox', 'remotegrade', get_string('ltj_remotegrade', 
	                   'qtype_ltjprocessed'));

    ///////////////////////////////////////////////////////////////
    // Answer elements.  Using repeated form so we can do more later, 
    // but only one will be presented for now
    $creategrades = get_grade_options();
    $gradeoptions = $creategrades->gradeoptions;
    $repeated        = array();
    $repeatedoptions = array();
    $repeated[] =& $mform->createElement('header', 'answerhdr', 
					 get_string('ltj_answerno', 'qtype_ltjprocessed', '{no}'));
    $repeated[] =& $mform->createElement('text', 'answer', 
					 get_string('answer', 'quiz'));
    $mform->setType('answer', PARAM_RAW);
    $repeated[] =& $mform->createElement('text', 'tolerance', 
					 get_string('acceptederror', 'quiz'));
    $mform->setType('tolerance', PARAM_RAW);
    $repeated[] =& $mform->createElement('select', 'fraction', 
					 get_string('grade'), $gradeoptions);
    $repeatedoptions['fraction']['default'] = 0;
    
    $repeated[] =& $mform->createElement('htmleditor', 'feedback', 
					 get_string('feedback', 'quiz'));
    $mform->setType('feedback', PARAM_RAW);
	
    if (isset($this->question->options) && 
	isset($this->question->options->answers)) {
      $countanswers = count($this->question->options->answers);
    } else {
      $countanswers = 0;
    }
    // find out how many answer repeated elements to start with
    $repeatsatstart = 1 > $countanswers ? 1 : $countanswers;
    $this->repeat_elements($repeated, $repeatsatstart, $repeatedoptions,
			   'noanswers', 'addanswers', 1, 
			   get_string('ltj_addmoreanswers', 'qtype_ltjprocessed'));
    
  }

  function set_data($question) {
    if (!empty($question->options)) {
      $question->serverid    = $question->options->serverid;
      $question->remotegrade = $question->options->remotegrade;
      $question->variables   = $question->options->variables;
      $question->imagecode   = $question->options->imagecode;
      $question->answers     = $question->options->answers;
      // load answers and associated extras here
      $answers = $question->options->answers;
      $key = 0;
      $default = array();
      foreach($answers as $answer) {
	$apd = '['.$key.']';
	$default['answer'.$apd]      = $answer->answer;
	$default['tolerance'.$apd]   = $answer->tolerance;
	$default['fraction'.$apd]    = $answer->fraction;
	$default['feedback'.$apd]    = $answer->feedback;
	$key++;
      }
      $question = (object)((array)$question + $default);
    }
    parent::set_data($question);
  }

  function validation($data) {
    $errors = array();

    // TODO, do extra validation on the data that came back from the form. E.g.
    // if (/* Some test on $data['customfield']*/) {
    //     $errors['customfield'] = get_string( ... );
    // }

    if ($errors) {
      return $errors;
    } else {
      return true;
    }
  }

  function qtype() {
    return 'ltjprocessed';
  }
}
?>
