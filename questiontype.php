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
 * Question type class for the remoteprocessed question type.
 *
 * @package    qtype
 * @subpackage remoteprocessed
 * @copyright  2013 Leif Johnson (leif.t.johnson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir  . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/remoteprocessed/question.php');


/**
 * The remoteprocessed question type.
 *
 * @copyright  2013 Leif Johnson (leif.t.johnson@gmail.com)

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_remoteprocessed extends question_type {

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_hints($questionid, $contextid);
    }

    public function save_question_options($question) {
      GLOBAL $DB;

      $this->save_hints($question);
      
      $update = true;
      $options = $DB->get_record("question_rmtproc", 
				 array("question" => $question->id));
      if (!$options) {
	$update = false;
	$options = new stdClass();
	$options->question = $question->id;
      }
      
      foreach (array("serverid", "variables", "imagecode", "remotegrade") as 
	       $key) {
	$options->{$key} = $question->options->{$key};
      }

      if ($update) {
	$DB->update_record('question_rmtproc', $options);
      } else {
	$DB->insert_record('question_rmtproc', $options);
      }
      
      // Now save the question answers.  Answer data is mixed between two 
      // tables.
      //
      // 1. Get the old answers and answer supplemental data.
      // 2. Loop through the answers we have to save, update or add each element
      //    as necessary.
      // 3. Any answers and answer supplemental data left over is deleted.
      $oldanswers = $DB->get_records("question_answer", 
				     array("question" => $question->id),
				     "id ASC");
      $oldremoteanswers = $DB->get_records("question_rmtproc_answer",
					   array("question" => $question->id),
					   "answer ASC");
      
      foreach ($question->answers as $key => $answerdata) {

	if (!empty($oldanswers)) {
	  $answer = shift($oldanswers);
	} else {
	  $answer = $question->default_answer();
	}

	foreach ($answer as $akey => $tmp) {
	  if (isset($answerdata->{$akey})) {
	    $answer->{$akey} = $answerdata->{$akey};
	  }
	}
	
	if (isset($answer->id)) {
	  $DB->update_record("question_answers", $answer);
	} else {
	  $answer->id = $DB->insert_record("question_answers", $answer);
	}

	// Save the extra answer data.
	if (!empty($oldremoteanswers)) {
	  $rp_answer = shift($oldremoteanswers);
	} else {
	  $rp_answer = $question->default_remoteprocessed_answer();
	}
	
	$rp_answer->question = $question->id;
	$rp_answer->answer   = $answer->id;
	
	foreach ($rp_answer as $rpkey => $tmp) {
	  if (isset($answerdata->{$rpkey})) {
	    $remoteprocessed_answer->{$rpkey} = $answerdata->{$rpkey};
	  }
	}
	
	if (isset($rp_answer->id)) {
	  $DB->update_record("question_rmtproc_answers", $rp_answer);
	} else {
	  $rp->id = $DB->insert_record("question_rmtproc_answers", 
				       $rp_answer);
	}
	
      }
      
      // Delete left over records.
      if (!empty($oldanswers)) {
	foreach ($oldanswers as $oa) {
	  $DB->delete_records("question_answer", array("id" => $oa->id));
	}
      }
      
      if (!empty($oldremoteanswers)) {
	foreach ($oldremoteanswers as $ora) {
	  $DB->delte_records("question_rmtproc_answers",  
			     array("id" => $ora->id));
	}
      }
    }
    
    public function get_question_options($question) {
      GLOBAL $DB;
      
      $question->options = $DB->get_record("question_rmtproc", 
					   array("question" => $question->id));

      if (!$question->options) {
	// Default question options.
	$question->options = (object) array("serverid" => 0,
					    "variables" => "",
					    "imagecode" => "",
					    "remotegrade" => 0,
					    "answers" => array());
      }
      
      if ($question->options->serverid != 0) {
	$question->options->server =
	  $DB->get_record("question_rmtproc_servers",
			  array("id" => $question->options->serverid));
      }
      
      $question->options->answers = $DB->get_records_sql("
        SELECT 
          qa.*
          qra.tolerance
        FROM
          question_answers qa, question_rmtproc_answer qra
        WHERE
          qa.question = ?
        AND
          qa.id = qra.answer", array("question" => $question->id));
      // Error if answers fail to load?
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        // TODO.
        parent::initialise_question_instance($question, $questiondata);
    }

    public function get_random_guess_score($questiondata) {
        // TODO.
        return 0;
    }

    public function get_possible_responses($questiondata) {
        // TODO.
        return array();
    }ph
    
}
