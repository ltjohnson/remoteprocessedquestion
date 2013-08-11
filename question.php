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
      return;
    }
    $this->questiontext = $xmlResponse->data->questiontext;
    $this->image = "";
    if (isset($xmlResponse->data->image)) {
      $this->image = $xmlResponse->data->image;
    } 
    $step->set_qt_var('_image', $this->image);
    $step->set_qt_var('_questiontext', $this->questiontext);
  }

  public function apply_attempt_state(question_attempt_step $step) {
    $this->questiontext = $step->get_qt_var('_questiontext');
    $this->image = $step->get_qt_var('_image');
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

    public function grade_response(array $response) {
        // TODO.
        $fraction = 0;
        return array($fraction, question_state::graded_state_for_fraction($fraction));
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
