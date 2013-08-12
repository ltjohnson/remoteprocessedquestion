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
 * remoteprocessed question definition class.
 *
 * @package    qtype
 * @subpackage remoteprocessed
 * @copyright  2013 Leif Johnson (leif.t.johnson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/locallib.php');

/**
 * Represents a remoteprocessed question.
 *
 * @copyright  2013 Leif Johnson (leif.t.johnson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_remoteprocessed_question extends question_graded_automatically_with_countback {

  public function start_attempt(question_attempt_step $step, $variant) {
    $request = $this->question_initialization_xmlrpc_request_args();
    $xmlResponse = xmlrpc_request($request);
    if (!$xmlResponse->success) {
      print "<br/><b>Error processing question.</b><br/>";
      print $xmlResponse->warning;
    }
    $this->questiontext = $xmlResponse->data->questiontext;
    $this->image = "";
    if (isset($xmlResponse->data->image)) {
      $this->image = $xmlResponse->data->image;
    } 

    if (!$this->options->remotegrade) {
      // Grading is to be done by the Moodle server, extract and store 
      // the grading information from the xmlResponse.
      $this->calculatedanswers = $xmlResponse->data->answers;
      $ansid_arr     = array();
      $answer_arr    = array();
      $tolerance_arr = array();
      foreach ($this->calculatedanswers as $answer) {
        $answer_arr[] = $answer['answer'];
        $ansid_arr[]  = $answer['ansid'];
        // There has to be an answer and ansid at each point, however, 
        // tolerance may be empty.
        if (isset($answer['tolerance'])) {
          $tolerance_arr[] = $answer['tolerance'];
        } else {
          // push an empty string if the tolerance is missing from this answer.
          $tolerance_arr[] = "";
        }
        
      }
      $step->set_qt_var('_answer', implode("@", $answer_arr));
      $step->set_qt_var('_ansid', implode("@", $ansid_arr));
      $step->set_qt_var('_tolerance', implode("@", $tolerance_arr));
    }

    $step->set_qt_var('_image', $this->image);
    $step->set_qt_var('_questiontext', $this->questiontext);
  }

  public function apply_attempt_state(question_attempt_step $step) {
    $this->questiontext = $step->get_qt_var('_questiontext');
    $this->image = $step->get_qt_var('_image');

    if (!$this->options->remotegrade) {
      $answer_arr    = explode("@", $step->get_qt_var('_answer'));
      $ansid_arr     = explode("@", $step->get_qt_var('_ansid'));
      $tolerance_arr = explode("@", $step->get_qt_var('_tolerance'));

      $numanswers = count($answer_arr);
      $answers = array();
      for ($i = 0; $i < $numanswers; $i++) {
        $answer = array(
          'answer' => $answer_arr[$i],
          'tolerance' => $tolerance_arr[$i],
          'ansid' => $ansid_arr[$i],
          );
        array_push($answers, $answer);
      }

      $this->calculatedanswers = $answers;
    }
  }

  public static $options_keys =
    array('serverid', 'variables', 'imagecode', 'remotegrade');
  
  public static function default_options() {
    return (object) array('serverid' => 0,
			  'variables' => '',
			  'imagecode' => '',
			  'remotegrade' => 0,
			  'answers' => array());
  }

    public function get_expected_data() {
        return array('answer' => PARAM_RAW_TRIMMED);
    }

    public function summarise_response(array $response) {
        if (!array_key_exists('answer', $response)) {
          return null;
        }
        return $response['answer'];
    }

    public function is_complete_response(array $response) {
        return array_key_exists('answer', $response);
    }

    public function get_validation_error(array $response) {
        // TODO.
        return '';
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        // TODO.
        return question_utils::arrays_same_at_key_missing_is_blank(
                $prevresponse, $newresponse, 'answer');
    }


    public function get_correct_response() {
        // TODO.
        return array();
    }


    public function check_file_access($qa, $options, $component, $filearea,
            $args, $forcedownload) {
        // TODO.
        if ($component == 'question' && $filearea == 'hint') {
            return $this->check_hint_file_access($qa, $options, $args);

        } else {
            return parent::check_file_access($qa, $options, $component, $filearea,
                    $args, $forcedownload);
        }
    }

    public function grade_response_locally(array $response) {
      $value = $response['answer'];
      if (is_null($value) || $value == '') {
        return array(0, question_state::graded_state_for_fraction(0));
      }
      $ansid = 0;
      foreach ($this->calculatedanswers as $answer) {
        // Find the first answer that matches the response.
        $difference = $answer['answer'] - $value;
        if (abs($difference) <= $answer['tolerance']) {
          $ansid = $answer['ansid'];
          break;
        }
      }

      $fraction = 0;

      if ($ansid > 0) {
        foreach ($this->options->answers as $answer) {
          if ($answer->id == $ansid) {
            $fraction = $answer->fraction;
            break;
          }
        }
      }
      
      return array($fraction, question_state::graded_state_for_fraction($fraction));
    }

    public function grade_response_remotely(array $response) {
      // TODO
      $fraction = 0;
      return array($fraction, question_state::graded_state_for_fraction($fraction));
    }

    public function grade_response(array $response) {
      if (isset($this->options->remotegrade) &&
          $this->options->remotegrade === 1) {
        return $this->grade_response_remotely($response);
      } else {
        return $this->grade_response_locally($response);
      }
    }

    public function compute_final_grade($responses, $totaltries) {
        // TODO.
        return 0;
    }

    // Functions for communicating with the remote server.
    private function question_initialization_xmlrpc_request_args() {
      // Create rpc request vars.
      $request = array();
      $request['variables'] = $this->options->variables;

      $imagecode = trim($this->options->imagecode);
      if ($imagecode) {
	       $request['imagecode'] = $imagecode;
      }
      $request['questiontext'] = $this->questiontext;
      $request['remotegrade'] = $this->options->remotegrade;
      $request['answers'] = array();
      
      foreach ($this->options->answers as $answer) {
	     array_push($request['answers'],
		   array('ansid'     => $answer->id, 
			 'answer'    => $answer->answer,
			 'tolerance' => $answer->tolerance));
      }
      
      $request['numanswers'] = count($request['answers']);

      return (object) array('server' => $this->options->server->url, 
		                        'method' => 'processquestion', 
		                        'args'   => $request);
    }

    // Some utility functions.
    public static function default_answer() {
      $answer = (object) array("answer" => "", 
			       "answerformat" => 1,
			       "fraction" => 1.0,
			       "feedback" => "",
			       "feedbackformat" => 0);
      return $answer;
    }
    
    public static function default_remoteprocessed_answer() {
      $answer = (object) array("tolerance" => "0.0");
      return $answer;
    }

}
