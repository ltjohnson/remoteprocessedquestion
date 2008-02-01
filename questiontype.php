<?php
/**
 * The question type class for the Remote Processed Question question type.
 *
 * @copyright &copy; 2007 Leif Johnson
 * @author leif.t.johnson@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ltjprocessed
 *//** */

/**
 * The Remote Processed Question question class
 *
 * TODO give an overview of how the class works here.
 */
require_once(dirname(__FILE__) . '/locallib.php');
class ltjprocessed_qtype extends default_questiontype
{

  function name() {
    return 'ltjprocessed';
  }
    
  // TODO think about whether you need to override the is_manual_graded or
  // is_usable_by_random methods form the base class. Most the the time you
  // Won't need to.

  /**
   * @return boolean to indicate success of failure.
   */
  function get_question_options(&$question) {
    $ltj_tbl     = 'question_ltjprocessed';
    $ltj_ans_tbl = 'question_ltjprocessed_answers';
    $ans_tbl     = 'question_answers';
    $question->options = get_record($ltj_tbl, 'question', $question->id);
    if (!$question->options) {
      return false;
    }
    $answers = get_records($ans_tbl, 'question', $question->id, 'id ASC');
    $question->options->answers = array();
    if (!$answers) { 
      return true;
    }
    foreach($answers as $answer) {
      $extra = get_record($ltj_ans_tbl, 'answer', $answer->id);
      if ($extra) {
	$answer->tolerance   = $extra->tolerance;
	$answer->remotegrade = $extra->remotegrade;
      } else {
	$answer->tolerance   = "0.0";
	$answer->remotegrade = 0;
      }
    }
    $question->options->answers = $answers;
    return true;
  }

  /**
   * Save the units and the answers associated with this question.
   * @return boolean to indicate success of failure.
   */
  function save_question_options($question) {
    $ltj_tbl     = 'question_ltjprocessed';
    $ltj_ans_tbl = 'question_ltjprocessed_answers';
    $ans_tbl     = 'question_answers';

    // get old answers and extra answer components
    $oldanswers = get_records($ans_tbl, 'question', $question->id, 'id ASC');
    if (!$oldanswers) { 
      $oldanswers = array();
    }
    $oldextras = get_records($ltj_ans_tbl, 'question', $question->id, 
			     'answer ASC');
    if (!$oldextras) { 
      $oldextras = array();
    }

    // Insert answers
    for($idx=0; $idx < $question->noanswers; $idx++) {
      // skip emtpy answers, this is possibly not the correct action
      if (trim($question->answer[$idx]) == '') {
	continue;
      }
      $answer = new stdClass;
      $answer->question   = $question->id;
      $answer->answer     = trim($question->answer[$idx]);
      $answer->fraction   = isset($question->fraction[$idx]) ?
	trim($question->fraction[$idx]) : 1.0;
      $answer->feedback   = ""; // TODO: answer feedback?
      //$answer->feedback = $question->feedback[$idx];
      
      $extra              = new stdClass;
      $extra->question    = $question->id;
      $extra->tolerance   = $question->tolerance[$idx];
      // if it is unchecked in the form, this is not set, work around
      $extra->remotegrade = isset($question->remotegrade[$idx]) ? 
	$question->remotegrade[$idx] : 0;
      
      // see if there is an old answer to use for this one, 
      // otherwise make a new one
      if ($oldanswer = array_shift($oldanswers)) {
	$answer->id = $oldanswer->id;
	if (!update_record("question_answers", $answer)) {
	  $result->error = "Could not update question answer!";
	  return $result;
	}
      } else { // new answer
	if (!$answer->id = insert_record($ans_tbl, $answer)) {
	  $result->error = "Could not insert question answer!";
	  return $result;
	}
      }
      // save answer id with extra answer
      $extra->answer = $answer->id;
      if ($oldextra = array_shift($oldextras)) {
	$extra->id = $oldextra->id;
	if (!update_record($ltj_ans_tbl, $extra)) {
	  $result->error = "Could not update question answer extras!";
	  return $result;
	}
      } else { // new extra 
	if (!$extra->id = insert_record($ltj_ans_tbl, $extra)) {
	  $result->error = "Could not insert question answer extras!";
	  return $result;
	}
      }
    } // foreach $answer
    
    // remove leftover answer/extra objects
    while ($answer = array_shift($oldanswers)) {
      delete_records($ans_tbl, 'id', $answer->id);
    }
    while ($extra = array_shift($oldextras)) {
      delete_records($ltj_ans_tbl, 'id', $extra->id);
    }

    // create new object to store our question
    $options            = new object;
    $options->question  = $question->id;
    $options->serverid  = $question->serverid;
    $options->variables = $question->variables;

    // save options
    if ($old = get_record($ltj_tbl, 'question', $question->id)) {
      $old->serverid  = $options->serverid;
      $old->variables = $options->variables;
      if (!update_record($ltj_tbl, $old)) {
	$result->error = 
	  "Could not update processed question options! (id=$old->id)";
	return $result;
      }
    } else {
      if (!insert_record($ltj_tbl, $options)) {
	$result->error = 'Could not insert processed question options!';
	return $result;
      }
    }
    return true;
  }

  /**
   * Deletes states from the question-type specific tables
   *
   * @param string $stateslist Comma separated list of state ids to be deleted
   * @return boolean to indicate success of failure.
   */
  function delete_states($stateslist) {
    $tbls = array('question_ltjprocessed_states',
		  'question_ltjprocessed_ans_states');
    foreach($tbls as $tbl) {
      delete_records_select($tbl, "state IN ($stateslist)");
    }
    return true;
  }
  /**
   * Deletes question from the question-type specific tables
   *
   * @param integer $questionid The question being deleted
   * @return boolean to indicate success of failure.
   */
  function delete_question($questionid) {
    $tbls = array('question_ltjprocessed',
		  'question_ltjprocessed_answers',
		  'question_answers');
    foreach($tbls as $tbl) {
      delete_records($tbl, "question", $questionid);
    }
    return true;
  }
  
  function process_question(&$question) {
    $ltj_serv_tbl = 'question_ltjprocessed_servers';
    $server = get_record($ltj_serv_tbl, 'id', $question->options->serverid);
    if (!$server) {
      return "";
    }
    // Create url
    $url = $server->serverurl;
    $urlvars = "submit=Process".
      "&questiontext=" . urlencode($question->questiontext) .
      "&variables=" . urlencode($question->options->variables) .
      "&numanswers=" . urlencode(count($question->options->answers));
    $key = 0;
    foreach ($question->options->answers as $answer) {
      $idx = "[" . $key . "]";
      $urlvars = $urlvars . 
	"&answer" . $idx . "=" . urlencode($answer->answer) .
	"&tolerance" . $idx . "=" . 
	(isset($answer->tolerance) ? urlencode($answer->tolerance) : "0.0") .
	"&ansid" . $idx . "=" . urlencode($answer->id);
      $key++;
    }
    // have the remote server proess the request
    $res = get_post_url_contents($url, $urlvars);
    $xml_data = simplexml_load_string($res);

    $ret = new stdClass;
    // now load the results
    if (isset($xml_data->questiontext) && trim($xml_data->questiontext) != '') {
      $ret->questiontext = trim($xml_data->questiontext);
    }

    $ansno = 0;
    $ret->answers = array();
    for($i = 0; $i < $xml_data->numanswers; $i++) {
      $answer = new stdClass;
      if (isset($xml_data->answer[$i]) && trim($xml_data->answer[$i]) != '') {
	$answer->answer = trim($xml_data->answer[$i]);
      } else {
	$answer->answer = "0";
      }
      if (isset($xml_data->tolerance[$i]) && trim($xml_data->tolerance[$i]) != '') {
	$answer->tolerance = trim($xml_data->tolerance[$i]);
      } else {
	$answer->tolerance = "0.0";
      }
      if (isset($xml_data->answerid[$i])) {
	$answer->id = intval($xml_data->answerid[$i]);
      } else {
	$answer->id = 0;
      }
      array_push($ret->answers, $answer);
    }
    $ret->numanswers = $xml_data->numanswers;
    return $ret;
  }

  function create_session_and_responses(&$question, &$state, $cmoptions, $attempt) {
    global $QTYPES;
    // remote process the question
    $newq = $QTYPES[$question->qtype]->process_question($question);
    if (isset($newq->questiontext)) {
      $question->questiontext = $newq->questiontext;
    }
    if (isset($newq->numanswers)) {
      // let's not be clever here, just try to match them up.
      // sort each by id
      usort($question->options->answers, "cmp_id");
      usort($newq->answers, "cmp_id");
      $j = 0; $maxj = count($question->options->answers);
      foreach($newq->answers as $answer) {
	while($maxj > $j && 
	      $question->options->answers[$j]->id != $answer->id) {
	  $j++;
	}
	if ($j >= $maxj) {
	  array_push($question->options->answers, $answer);
	} else {
	  $question->options->answers[$j]->answer    = $answer->answer;
	  $question->options->answers[$j]->tolerance = $answer->tolerance;
	}
      }
    }
    $state->responses = array('' => "");
    return true;
  }

  function restore_session_and_responses(&$question, &$state) {
    $ltj_state_tbl     = 'question_ltjprocessed_states';
    $ltj_ansstates_tbl = 'question_ltjprocessed_ans_states';

    $ltjstate = get_record($ltj_state_tbl, 'state', $state->id);
    if ($ltjstate) {
      $question->questiontext = $ltjstate->questiontext;
    }
    $ltjanswers = get_records($ltj_ansstates_tbl, 'state', $state->id, 
			      'answer ASC');
    if ($ltjanswers) {
      // sort existing answers by answer id
      usort($question->options->answers, "cmp_id");
      $j = 0; $maxj = count($question->options->answers);
      // Possibly check count's of this answers vs what the question has
      foreach($ltjanswers as $answer) {
	while($j < $maxj &&
	      $question->options->answers[$j]->id != $answer->answer) {
	  $j++;
	}
	if ($j < $maxj) {
	  $question->options->answers[$j]->answer    = $answer->answertext;
	  $question->options->answers[$j]->tolerance = $answer->tolerance;
	} else {
	  $ans            = new stdClass;
	  $ans->id        = $answer->answer;
	  $ans->answer    = $anwser->answertext;
	  $ans->tolerance = $answer->tolerance;
	  array_push($question->options->answers, $ans);
	}
      }
    }
    // lastly restore the response
    if (empty($state->responses) || empty($state->responses[''])) {
      $state->responses = array('' => "");
    }
    return true;
  }
    
  function save_session_and_responses(&$question, &$state) {
    $ltj_state_tbl     = 'question_ltjprocessed_states';
    $ltj_ansstate_tbl = 'question_ltjprocessed_ans_states';

    // save the ltj_state info
    $oldstates = get_records($ltj_state_tbl, 'state', $state->id, 'id ASC');
    if (!$oldstates) {
      $oldstates = array();
    }
    $ltjstate = new stdClass;
    $ltjstate->state        = $state->id;
    $ltjstate->attempt      = $state->attempt;
    $ltjstate->question     = $state->question;
    $ltjstate->questiontext = $question->questiontext;
    if (count($oldstates)) {
      $old = array_shift($oldstates);
      $ltjstate->id = $old->id;
      if (!update_record($ltj_state_tbl, $ltjstate)) {
	$result->error = "Could not update record in ".$ltj_state_tbl."!";
	return $result;
      }
      while($old = array_shift($oldstates)) {
	delete_records($ltj_state_tbl, 'id', $old->id);
      }
    } else {
      if (!$ltjstate->id = insert_record($ltj_state_tbl, $ltjstate)) {
	$result->error = "Could not insert question state into ". 
	  $ltj_state_tbl . "!";
	return $result;
      }
    }
    // save the extra answer states now
    $oldanswers = get_records($ltj_ansstate_tbl, 'state', $state->id, 'id ASC');
    if (!$oldanswers) {
      $oldanswers = array();
    }
    
    foreach($question->options->answers as $answer) {
      $ltjans = new stdClass;
      $ltjans->attempt    = $state->attempt;
      $ltjans->state      = $state->id;
      $ltjans->answer     = $answer->id;
      $ltjans->question   = $question->id;
      $ltjans->answertext = "".$answer->answer;
      $ltjans->tolerance  = "".$answer->tolerance;
      // crude length protection
      if (strlen($ltjans->tolerance) > 254) {
	$ltjans->tolerance[254] = "\0";
      }
      if ($oldans = array_shift($oldanswers)) {
	$ltjans->id = $oldans->id;
	if (!update_record($ltj_ansstate_tbl, $ltjans)) {
	  $result->error = "Could not update table ".$ltj_ansstate_tbl."!";
	  return $result;
	}
      } else {
	$ltjans->id = insert_record($ltj_ansstate_tbl, $ltjans);
	if (!$ltjans->id) {
	  $result->error = "Could not insert into ".$ltj_ansstate_tbl."!";
	  return $result;
	}
      }
    }

    while($old = array_shift($oldanswers)) {
      delete_records($ltj_ansstate_tbl, 'id', $old->id);
    }
    // package up the responses array
    $this->log_response($state->responses, "save_session_and_responses");
    $responses = $state->responses[''];
    return set_field('question_states', 'answer', $responses, 'id', $state->id);
  }
  
  function log_response($responses, $function="<none>") {
    /*
    $lfile = fopen("/var/log/ltj.log", "a");
    fwrite($lfile, "function: ". $function."\n");
    fwrite($lfile, "responses: ".$responses."\n");
    foreach($responses as $key => $value) {
      fwrite($lfile, "$key = $value \n");
    }
    fflush($lfile);
    fclose($lfile);
    */
  }

  function print_question_formulation_and_controls(&$question, &$state, 
						   $cmoptions, $options) {
    global $CFG;

    $readonly = empty($options->readonly) ? '' : 'disabled="disabled"';
    $inputname = $question->name_prefix;
    $value = $state->responses[''];
    
    // Print formulation
    $questiontext = $this->format_text($question->questiontext,
				       $question->questiontextformat, 
				       $cmoptions);
    $image = get_question_image($question, $cmoptions->course);
    
    // we could do fancier stuff for the answer form here
    // need to respect read only here etc.  should also preserve student 
    // responses
    if (empty($readonly)) {
      $answer_form = '<input name="'.$inputname.'" type="text" size="12" value="'.
	$value.'"/>';
    } else {
      $answer_form = $value;
    }
    
    // TODO prepare any other data necessary. For instance
    $feedback = '';
    if ($options->feedback) {
      
    }
    
    include("$CFG->dirroot/question/type/ltjprocessed/display.html");
  }
    
  /**
   * Checks whether a response matches a given answer, taking the tolerance
   * and units into account. Returns a true for if a response matches the
   * answer, false if it doesn't.
   */
  function test_response(&$question, &$state, $answer) {
    // TODO: increase robustness/error handling
    // we'll just do the simplest thing possible for now
    $student_answer = $state->responses[''];
    if (trim($student_answer) == '') {
      return false;
    }
    // convert to numbers/floats?
    $res = abs($answer->answer - $student_answer);
    if ($res <= $answer->tolerance) {
      return true;
    } 

    return false;
  }

  function check_response(&$question, &$state){
    $answers = &$question->options->answers;
    foreach ($answers as $answer) {
      if ($this->test_response($question, $state, $answer)) {
	return $answer->id;
      }
    }
    return false;
  }

  function get_correct_responses(&$question, &$state) {
    $ret = array();
    foreach($question->options->answers as $answer) {
      if (1 == $answer->fraction) {
	$ret[$answer->id] = (string)$answer->id;
      }
    }
    return empty($ret) ? null : $ret;
  }

  /**
   * Backup the data in the question
   *
   * This is used in question/backuplib.php
   */
  function backup($bf,$preferences,$question,$level=6) {
    $status = true;

    // TODO write code to backup an instance of your question type.

    return $status;
  }

  /**
   * Restores the data in the question
   *
   * This is used in question/restorelib.php
   */
  function restore($old_question_id,$new_question_id,$info,$restore) {
    $status = true;

    // TODO write code to restore an instance of your question type.

    return $status;
  }
}
// Register this question type with the system.
question_register_questiontype(new ltjprocessed_qtype());
?>
