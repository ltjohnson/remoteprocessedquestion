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
    $question->options = get_record(ltj_tbl(), 'question', $question->id);
    if (!$question->options) {
      return false;
    }
    $answers = get_records(ans_tbl(), 'question', $question->id, 'id ASC');
    $question->options->answers = array();
    if (!$answers) { 
      return true;
    }
    foreach($answers as $answer) {
      $extra = get_record(ltj_ans_tbl(), 'answer', $answer->id);
      if ($extra) {
	$answer->tolerance   = $extra->tolerance;
      } else {
	$answer->tolerance   = "0.0";
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
    // get old answers and extra answer components
    $oldanswers = get_records(ans_tbl(), 'question', $question->id, 'id ASC');
    if (!$oldanswers) { 
      $oldanswers = array();
    }
    $oldextras = get_records(ltj_ans_tbl(), 'question', $question->id, 
			     'answer ASC');
    if (!$oldextras) { 
      $oldextras = array();
    }

    // Insert answers
    $numanswers = count($question->answer);
    for($idx=0; $idx < $numanswers; $idx++) {
      // skip emtpy answers, this is possibly not the correct action
      if (trim($question->answer[$idx]) == '') {
	continue;
      }
      $answer = new stdClass;
      $answer->question   = $question->id;
      $answer->answer     = trim($question->answer[$idx]);
      $answer->fraction   = isset($question->fraction[$idx]) ?
	trim($question->fraction[$idx]) : 1.0;
      $answer->feedback = trim($question->feedback[$key]);
      
      $extra              = new stdClass;
      $extra->question    = $question->id;
      $extra->tolerance   = $question->tolerance[$idx];
      
      // see if there is an old answer to use for this one, 
      // otherwise make a new one
      if ($oldanswer = array_shift($oldanswers)) {
	$answer->id = $oldanswer->id;
	if (!update_record("question_answers", $answer)) {
	  $result->error = "Could not update question answer!";
	  return $result;
	}
      } else { // new answer
	if (!$answer->id = insert_record(ans_tbl(), $answer)) {
	  $result->error = "Could not insert question answer!";
	  return $result;
	}
      }
      // save answer id with extra answer
      $extra->answer = $answer->id;
      if ($oldextra = array_shift($oldextras)) {
	$extra->id = $oldextra->id;
	if (!update_record(ltj_ans_tbl(), $extra)) {
	  $result->error = "Could not update question answer extras!";
	  return $result;
	}
      } else { // new extra 
	if (!$extra->id = insert_record(ltj_ans_tbl(), $extra)) {
	  $result->error = "Could not insert question answer extras!";
	  return $result;
	}
      }
    } // foreach $answer
    
    // remove leftover answer/extra objects
    while ($answer = array_shift($oldanswers)) {
      delete_records(ans_tbl(), 'id', $answer->id);
    }
    while ($extra = array_shift($oldextras)) {
      delete_records(ltj_ans_tbl(), 'id', $extra->id);
    }

    // create new object to store our question
    $options              = new object;
    $options->question    = $question->id;
    $options->serverid    = $question->serverid;
    $options->variables   = $question->variables;
    $options->imagecode   = $question->imagecode;
    $options->remotegrade = 
	    isset($question->remotegrade) ? $question->remotegrade : 0;


    // save options
    if ($old = get_record(ltj_tbl(), 'question', $question->id)) {
      $old->serverid    = $options->serverid;
      $old->variables   = $options->variables;
      $old->imagecode   = $options->imagecode;
      $old->remotegrade = $options->remotegrade;
      if (!update_record(ltj_tbl(), $old)) {
	$result->error = 
	  "Could not update processed question options! (id=$old->id)";
	return $result;
      }
    } else {
      if (!insert_record(ltj_tbl(), $options)) {
	$result->error = 'Could not insert processed question options!';
	return $result;
      }
    }
    return true;
  }

  /**
   * Deletes states for the question-type
   *
   * @param string $stateslist Comma separated list of state ids to be deleted
   * @return boolean to indicate success of failure.
   */
  function delete_states($stateslist) {
    global $CFG;
    
    //echo "Called ltjprocessed->delete_states with states ".$stateslist."</br>\n";

    $states = explode(",", $stateslist);
    
    foreach($states as $stateid) {
      $state = get_record("question_states", "id", "$stateid");
      $attemptid  = $state->attempt;
      $questionid = $state->question;
      
      // Delete files for this attempt.  This actually deletes files for 
      // ALL of the questions for this attempt, but that is okay because
      // this function is never called unless the entire attempt is being 
      // deleted.
      $dir = question_file_area_name($attemptid, $questionid);
      if (file_exists($CFG->dataroot.'/'.$dir)) {
	fulldelete($CFG->dataroot.'/'.$dir);
	// delete the second level if there is one
	$dirparts = explode('/', $dir);
	array_pop($dirparts);
	$dir = implode('/', $dirparts);
	if (file_exists($CFG->dataroot.'/'.$dir)) {
	  fulldelete($CFG->dataroot.'/'.$dir);
	}
      }
    } // foreach $state
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
  
  /******************************************************************
   * The actual remote stuff
   */
  function process_question(&$question, $attemptid) {
    $server = get_record(ltj_serv_tbl(), 'id', $question->options->serverid);
    if (!$server) {
      return "";
    }
    // convert question into request vars
    $request = array();
    $request['variables']    = $question->options->variables;
    if (trim($question->options->imagecode)) {
      $request['imagecode']    = trim($question->options->imagecode);
    }
    $request['questiontext'] = $question->questiontext;
    $request['numanswers']   = count($question->options->answers);
    $request['remotegrade']  = $question->options->remotegrade;
    $request['answers']      = array();
    foreach($question->options->answers as $answer) {
      $ans = array();
      $ans['ansid']     = $answer->id;
      $ans['answer']    = $answer->answer;
      $ans['tolerance'] = $answer->tolerance;
      array_push($request['answers'], $ans);
    }

    $url    = $server->serverurl;
    $method = 'processquestion';

    $processed = xmlrpc_request($url, $method, $request);
    if (array_key_exists('faultCode', $processed)) {
      echo "Error processing question: faultCode[". $processed['faultCode'];
      echo "], faultString[".$processed['faultString']."]</br>\n";
      return NULL; 
    }
    // post process variables, mainly to make sure we don't end up with 
    // something completely bogus
    $ret = new stdClass;
    if (isset($processed['questiontext']) && 
	trim($processed['questiontext']) != '') {
      $ret->questiontext = $processed['questiontext'];
    }
    /*****************************************************************
     * Images and workpspaces are sent as base64 encoded blobs.  We need 
     * to decode them and save them in an appropriate place
     */
    if (array_key_exists('image', $processed) && isset($processed['image'])) {
      $imgdata = base64_decode($processed['image']);
      // get the image dir
      $dir = question_file_area_name($attemptid, $question->id);
      $basedir = question_file_area($dir);
      if($basedir) {
	// TODO: make this name unique;
	$imgname = "genimage.png";
	$imgfile = fopen($basedir ."/".$imgname, "w");
	fwrite($imgfile, $imgdata);
	fclose($imgfile);
	$ret->genimage = $imgname;
      }
    }
    
    if (array_key_exists('workspace', $processed) && 
	isset($processed['workspace'])) {
      // we don't really need to decode it, as all we are going to do
      // is send it back, but this *should* save a little diskspace over
      // writing the file as base64_encoded 
      $workdata = base64_decode($processed['workspace']);
      $dir = question_file_area_name($attemptid, $question->id);
      $basedir = question_file_area($dir);
      if($basedir) {
	// TODO: make this name unique.
	$workname = "workspace.dat";
	$workfile = fopen($basedir ."/". $workname, "w");
	fwrite($workfile, $workdata);
	fclose($workfile);
	$ret->workspace = $workname;
      }
    }
    /*****************************************************************
     */
    // go over processed answers
    $ansno = 0;
    $ret->answers = array();
    foreach($processed['answers'] as $ans) {
      $answer = new stdClass;
      if (isset($ans['answer']) && trim($ans['answer']) != '') {
	$answer->answer = $ans['answer'];
      } else {
	$answer->answer = "0";
      }
      if (isset($ans['tolerance']) && trim($ans['tolerance']) != '') {
	$answer->tolerance = $ans['tolerance'];
      } else {
	$answer->tolerance = "0";
      }
      if (isset($ans['ansid']) && trim($ans['ansid']) != '') {
	$answer->id = $ans['ansid'];
      } else {
	$answer->id = 0;
      }
      array_push($ret->answers, $answer);
    }

    $ret->numanswers = count($ret->answers);
    return $ret;
  }

  function remotegrade_question(&$question, &$state) {
    global $CFG;

    $server = get_record(ltj_serv_tbl(), 'id', $question->options->serverid);
    if (!$server) {
      return "";
    }

    $request = array();

    // attach workspace if it exists
    if ($question->options->workspace) {
      $filename = $CFG->dataroot . '/' . 
	question_file_area_name($state->attempt, $question->id) .
	"/". $question->options->workspace;
      $filein = fopen($filename, "rb");
      $filedata = fread($filein, filesize($filename) );
      fclose($filein);
      $request['workspace'] = base64_encode($filedata);
    }

    $request['studentans'] = $state->responses[''];

    // attach answers
    if ($question->options->answers) {
      $request['answers'] = array();
      foreach($question->options->answers as $answer) {
	$ans           = array();
	$ans['ansid']  = $answer->id;
	$ans['answer'] = $answer->answer;
	array_push($request['answers'], $ans);
      }
    }

    // Finally send the request to the server
    $url    = $server->serverurl;
    $method = 'grade';
    $result = xmlrpc_request($url, $method, $request);
    /* check for errors, fail gracefully, blah blah blah */
    $ret = array();
    foreach($result as $r) {
      if ($r != 0) {
	$ret[$r] = 1;
      }
    }
    return $ret;
  }

  /*****************************************************************/
  function create_session_and_responses(&$question, &$state, $cmoptions, $attempt) {
    global $QTYPES;
    // remote process the question
    $newq = $QTYPES[$question->qtype]->process_question($question, $attempt->id);
    if ($newq != NULL && isset($newq->questiontext)) {
      $question->questiontext = $newq->questiontext;
    }
    if ($newq != NULL && isset($newq->genimage)) {
      $question->options->genimage = $newq->genimage;
    }
    if ($newq != NULL && isset($newq->workspace)) {
      $question->options->workspace = $newq->workspace;
    }
    
    if ($newq != NULL && isset($newq->numanswers)) {
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
  
  /*
  function log_response($str) {
    $fp = fopen("/Users/leif/log/php.log", "a");
    fwrite($fp, $str);
    fclose($fp);
  }
  */
  function restore_session_and_responses(&$question, &$state) {
    // Bail if there isn't anything for us to work on
    if (empty($state->responses) || empty($state->responses[''])) {
      $state->responses = array('' => "");
      return true;
    }
    // recover the urlencoded strings we stored
    $output = array();
    parse_str($state->responses[''], $output);
    /*****************************************************************
     * The simple things are in this section, just grabe the decoded
     * output
     */
    if (array_key_exists("sans", $output)) {
      $state->responses[''] = urldecode($output["sans"]);
    } else {
      $state->responses[''] = "";
    }

    if (array_key_exists("ansid", $ouput)) {
      $state->ansid = urldecode($output['ansid']);
    }

    if (array_key_exists('qtext', $output)) {
      $question->questiontext = urldecode($output['qtext']);
    }
    
    if (array_key_exists('genimage', $output)) {
      $question->options->genimage = urldecode($output['genimage']);
    }
    
    if (array_key_exists('genimage', $output)) {
      $question->options->genimage = urldecode($output['genimage']);
    }
    
    if (array_key_exists('workspace', $output)) {
      $question->options->workspace = urldecode($output['workspace']);
    }
    
    // build our own answers, once we have them we will try to match
    // them to existing answers
    $answers = array();
    ltj_add_elements($answers, "id", $output, "aid");
    ltj_add_elements($answers, "answer", $output, "answer");
    ltj_add_elements($answers, "tolerance", $output, "tolerance");
    // no need to be clever, just sort each by id then match
    usort($question->options->answers, "cmp_id");
    usort($answers, "cmp_id");
    $j = 0; $maxj = count($question->options->answers);
    // This cruddy for loop is to keep thing synced by answer id
    foreach($answers as $answer) {
      while($j < $maxj && 
	      $answer->id != $question->options->answers[$j]->id) {
	  $j++;
	}
      if ($j < $maxj) {
	$question->options->answers[$j]->answer = $answer->answer;
	$question->options->answers[$j]->tolerance = $answer->tolerance;
      }
    }
    
    return true;
  }
    
  function save_session_and_responses(&$question, &$state) {

    $student_ans = ""; 
    if (!(empty($state->responses) && empty($state->responses['']))) {
      $student_ans = $state->responses[''];
    }
    // build an array to implode and store
    $state_str = "sans=".urlencode($student_ans).
      "&qtext=".urlencode($question->questiontext);

    if (isset($state->ansid)) {
      $state_str .= "&ansid=".urlencode($state->ansid);
    }
    // add the generated image if we have one
    if (isset($question->options->genimage)) {
      $state_str .= "&genimage=".urlencode($question->options->genimage);
    }
    
    if (isset($question->options->workspace)) {
      $state_str .= "&workspace=".urlencode($question->options->workspace);
    }
    
    // now encode the answers
    $state_str .= "&nans=".urlencode(count($question->options->answers));
    
    foreach($question->options->answers as $ans) {
      $state_str .= 
	"&aid[]=".urlencode($ans->id).
	"&answer[]=".urlencode($ans->answer).
	"&tolerance[]=".urlencode($ans->tolerance);
    }
    return set_field('question_states', 'answer', $state_str, 'id', $state->id);
  }

  function print_question_formulation_and_controls(&$question, &$state, 
						   $cmoptions, $options) {
    global $CFG;

    $readonly = empty($options->readonly) ? '' : 'disabled="disabled"';
    $inputname = $question->name_prefix;
    $value = $state->responses[''];

    $answers = &$question->options->answers;
    
    // Print formulation
    $questiontext = $this->format_text($question->questiontext,
				       $question->questiontextformat, 
				       $cmoptions);
    $image = get_question_image($question, $cmoptions->course);
    
    $genimage = NULL;
    if (isset($question->options->genimage)) {
      $genimage = question_file_area_name($state->attempt, $question->id);
      $genimage .= "/".$question->options->genimage;
      // remove the questionattempt from the start of this path
      $genimagelist = explode("/", $genimage);
      $fileurl = implode("/", array_slice($genimagelist, 1));
      if ($CFG->slasharguments) {
	$genimage = "$CFG->wwwroot/question/file.php/$fileurl";
      } else {
	$genimage = "$CFG->wwwroot/question/file.php?file=/$fileurl";
      }
    }
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
    if ($options->feedback && $state->ansid) {
      foreach($question->options->answer as $answer) {
	if ($answer->id == $state->ansid) {
	  $feedback = $answer->feedback;
	  break;
	}
      }
      $feedback = $this->format_text($feedback, 
				     $question->questiontextformat, 
				     $cmoptions);
    }
    include("$CFG->dirroot/question/type/ltjprocessed/display.html");
  }
    
  /**
   * Checks whether a response matches a given answer, taking the tolerance
   * and units into account. Returns a true for if a response matches the
   * answer, false if it doesn't.
   */
  function test_response(&$question, &$state, $answer) {
    global $QTYPES;

    // TODO: increase robustness/error handling
    // we'll just do the simplest thing possible for now
    $student_answer = $state->responses[''];
    if (trim($student_answer) == '') {
      return false;
    }
    if ($question->options->remotegrade == 1) { 
      if (!isset($state->matchedanswers)) {
	// remote process the answers to get the matching ones
	$state->matchedanswers = 
	  $QTYPES[$question->qtype]->remotegrade_question($question, $state);

      }
      if (array_key_exists($answer->id, $state->matchedanswers)) {
	$state->ansid = $answer->id;
	return true;
      }
    } else {
      // do basic grading inside of moodle
      // convert to numbers/floats?
      $res = abs($answer->answer - $student_answer);
      if ($res <= $answer->tolerance) {
	$state->ansid = $answer->id;
	return true;
      } 
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

  /*
  function grade_responses(&$question, &$state, $cmoptions) {

    // this is custom so we can do remote grading
    // we break it into two sections, 1 for grade in moodle, 
    // 1 for remote grading, each method simply sets ansid to be
    // 0 if there is no match, and the ansid of the first matching answer 
    // otherwise
    $state->ansid = 0;
    $state->raw_grade = 0;
    if ($question->options->remotegrade) {
      // remote process the question
      $res = 
      $state->ansid = $res->ansid;
      $state->raw_grade = $res->fraction;
    } else {
      foreach($question->options->answers as $answer) {
	if ($this->test_response($question, $state, $answer)) {
	  $state->ansid = $answer->id;
	  $state->raw_grade = $answer->fraction;
	  break;
	}
      }
    } // moodle grade
    
    echo "grade_responses selected ansid ". $state->ansid ."</br>\n";
    echo "raw grade: $state->raw_grade </br>\n";
    // moodle normalize the grade, this is blatantly copied from the base class
    // function
    $state->raw_grade = min(max((float) $state_raw_grade, 
				0.0), 1.0) * $question->maxgrade;
    $state->penalty = $question->penalty * $question->maxgrade;

    // mark state as graded
    $state->event = ($state->event == QUESTION_EVENTCLOSE) ?
      QUESTION_EVENTCLOSEANDGRADE : QUESTION_EVENTGRADE;

    return true;

  } // grade_responses
  */
  /**************************************************************
   * Backup the data in the question
   *
   * This is used in question/backuplib.php
   */
  function backup($bf,$preferences,$question,$level=6) {
    $status = true;
    
    /* All of this extra status tracking makes the code harder to read, 
     * the question is, is it even necessary?
     */

    $ltj_qs = get_records(ltj_tbl(), 'question', $question, 'id');
    foreach ($ltj_qs as $ltj_question) {
      $status = fwrite($bf,start_tag("LTJPROCESSED",$level,true)) && $status;
      // print contents
      
      // server first, it gets it's own sub field
      $server = get_record(ltj_serv_tbl(), "id", $ltj_question->serverid);
      $status = fwrite($bf,start_tag("SERVER", $level+1, true)) && $status;
      $status = fwrite($bf,full_tag("ID", $level+2, false, 
				    $ltj_question->serverid)) && $status;
      if ($server) {
	$status = fwrite($bf,full_tag("NAME", $level+2, false, 
				      $server->servername)) && $status;
	$status = fwrite($bf,full_tag("URL", $level+2, false, 
				      $server->serverurl)) && $status;
      }
      $status = fwrite($bf,end_tag("SERVER", $level+1, true)) && $status;
      $status = fwrite($bf,full_tag("VARIABLES", $level+1,false,
				    $ltj_question->variables)) && $status;
      $status = fwrite($bf,full_tag("IMAGECODE", $level+1,false,
				    $ltj_question->imagecode)) && $status;
      $status = fwrite($bf,full_tag("REMOTEGRADE", $level+1,false,
				    $ltj_question->remotegrade)) && $status;
      
      // write our extra answer fields
      $answers = get_records(ltj_ans_tbl(), 'question', $ltj_question->question, 'id ASC');
      if ($answers) {
	$tmp_status = fwrite($bf,start_tag("ANSWERS", $level+1, true));
	$status = $status && $tmp_status;
	foreach($answers as $ans) {
	  $status = fwrite($bf,start_tag("ANSWER", $level+2, true)) && $status;
	  $status = fwrite($bf,full_tag("ID",$level+3,false,$ans->answer)) && $status;
	  $status = fwrite($bf, full_tag("TOLERANCE", $level+3, false, $ans->tolerance)) && $status;
	  $status = fwrite($bf,end_tag("ANSWER", $level+2, true)) && $status;
	}
	$status = fwrite($bf,end_tag("ANSWERS", $level+1, true)) && $status;
      }
      // end our tag
      $status = fwrite($bf,end_tag("LTJPROCESSED",$level,true)) && $status;

    }
    
    // now print the question answers
    $status = question_backup_answers($bf, $preferences, $question, $level) && $status;

    return $status;
  }

  /**********************************************************************
   * Restores the data in the question
   *
   * This is used in question/restorelib.php
   */
  function restore($old_question_id,$new_question_id,$info,$restore) {
    $status = true;

    /*********************************************************************/
    // NOTE:
    // Pulling stuff out of the $info array is more or less magic, I just
    // copy what is done elsewhere in the code.
    /*********************************************************************/

    // get the question array
    $ltjqs = $info['#']['LTJPROCESSED'];

    // Iterate over the questions
    for($i=0; $i < sizeof($ltjqs); $i++) {
      // grab the current question
      $ltj_info = $ltjqs[$i];
      $ltjq = new stdClass;
      $ltjq->question = $new_question_id;
      $ltjq->variables = backup_todb($ltj_info['#']['VARIABLES']['0']['#']);
      $ltjq->imagecode = backup_todb($ltj_info['#']['IMAGECODE']['0']['#']);
      $ltjq->remotegrade = backup_todb($ltj_info['#']['REMOTEGRADE']['0']['#']);
      
      /********************************************************************/
      // Clever stuff to keep the server table sane is hidden in
      // restore_server_record
      $server_junk = $ltj_info['#']['SERVER'];
      $server_info = $server_junk[0];
      $server = new stdClass;
      $server->id = backup_todb($server_info['#']['ID']['0']['#']);
      $server->servername = backup_todb($server_info['#']['NAME']['0']['#']);
      $server->serverurl = backup_todb($server_info['#']['URL']['0']['#']);
      $ltjq->serverid = restore_server_record($server, "RESTORED");
      /********************************************************************/
      // Now make the answers line up properly

      $answer_arr = array();
      $answers_info = $ltj_info['#']['ANSWERS'];
      // iterate over each saved answer
      for($j=0; $j < count($answers_info); $j++) {
	$ans_info = $answers_info[$j];
	$ans = new stdClass;
	$ans->tolerance = backup_todb($ans_info['#']['TOLERANCE']['0']['#']);
	$ans->answer = backup_todb($ans_info['#']['ID']['0']['#']);
	$ans->question = $new_question_id;

	/* this is magic to make our answer id's match with what was restored */
	$answer = backup_getid($restore->backup_unique_code, 
			       "question_answers", $ans->id);
	if ($answer) {
	  $ans->answer = $answer->new_id;
	}
	// save the restored custom answers
	array_push($answer_arr, $ans);
      }
      /********************************************************************/
      // In theory our question now matches what was saved, so we now
      // save everything into the db
      $ltjq->id = insert_record(ltj_tbl(), $ltjq);
      foreach($answer_arr as $ans) {
	$ans->id = insert_record(ltj_ans_tbl(), $ans);
      }
      /********************************************************************/
    } // end of iterate over questions
    
    return $status;
  }
  /**********************************************************************
   * Provide import functionality for xml format
   * @param data mixed the segment of data containing the question
   * @param question object processed (so far) by standard import code
   * @param format object so that helper methods can be used (in 
   *               particular error())
   * @param extra mixed any additional format specific data that may be passed 
   *        by the format (see format code for info)
   * @return object question suitable for save_options() call or false if 
   *                cannot handle
   */
  function import_from_xml( $data, $question, $format, $extra=null ) {
    if (!array_key_exists('@', $data)) {
      return false;
    }
    if (!array_key_exists('type', $data['@'])) {
      return false;
    }
    if ($data['@']['type'] != 'ltjprocessed') {
      return false;
    }
    $question = $format->import_headers($data);
    $question->qtype = 'ltjprocessed';
    // grab our chunk
    $ltjprocessed = $data['#']['ltjprocessed']['0']['#'];
    $question->variables = $ltjprocessed['variables']['0']['#'];
    $question->imagecode = $ltjprocessed['imagecode']['0']['#'];
    $question->remotegrade = $ltjprocessed['remotegrade']['0']['#'];
    // do server processing
    $serv = $ltjprocessed['server']['0']['#'];

    $server = new stdClass;
    $server->id         = $serv['id']['0']['#'];
    $server->servername = $serv['servername']['0']['#'];
    $server->serverurl  = $serv['serverurl']['0']['#'];
    $question->serverid = restore_server_record($server, "IMPORTED");

    $answers = $ltjprocessed['answers']['0']['#']['answer'];
    for($i = 0; $i< count($answers); $i++) {
      $ans = $answers[$i]['#'];
      $question->answer[$i]    = $ans['answer']['0']['#'];
      $question->tolerance[$i] = $ans['tolerance']['0']['#'];
      $question->fraction[$i]  = $ans['fraction']['0']['#'];
      $question->feedback[$i]  = $ans['feedback']['0']['#'];
    }

    return $question;
    
  } // function import_from_xml
  /**********************************************************************
   * Provide export functionality for xml format
   * @param question object the question object
   * @param format object the format object so that helper methods can be used 
   * @param extra mixed any additional format specific data that may be passed 
   *        by the format (see format code for info)
   * @return string the data to append to the output buffer or false if error
   */
  function export_to_xml( $question, $format, $extra=null ) {
    // To use the xml stuff, you need to make an entire document, but you can 
    // print only the nodes you want, so it's not a big deal
    
    $doc = new DOMDocument();
    $tree = $doc->createElement('ltjprocessed');
    $tree->setAttribute("type", $this->name());
    $doc->appendChild($tree);

    // Now append all of the question info. We attempt to obey the moodle xml 
    // format as much as possible
    //
    // NOTE: Moodle prints the basic question info that's part of all questions,
    //       so we just need to print our own stuff + answers
    $serverml = $this->make_server_xml($doc, $question->options->serverid);
    if ($serverml) {
      $tree->appendChild($serverml);
    }

    $varsml = $doc->createElement('variables', $question->options->variables);
    $tree->appendChild($varsml);
    $imgml = $doc->createElement('imagecode', $question->options->imagecode);
    $tree->appendChild($imgml);
    $remoteml = $doc->createElement('remotegrade', 
	                                $question->options->remotegrade);
    $tree->appendChild($remoteml);

    $answersml = $doc->createElement("answers");
    $tree->appendChild($answersml);
    foreach($question->options->answers as $answer) {
      $answersml->appendChild($this->make_answer_xml($doc, $answer));
    }

    return $doc->saveXML($tree); // return just our node
  }

  /*
   * make_answer_xml 
   * @param doc    -- the document element (class DOMDocument) used to generate 
   *                  the nodes
   * @param answer -- the answer to turn into an xml node
   * @return a XMLelement as created by $doc->createElement
   */
  function make_answer_xml($doc, $answer) {
    $ansml = $doc->createElement('answer');
    
    $ansml->appendChild($doc->createElement('answer', $answer->answer));
    $ansml->appendChild($doc->createElement('fraction', $answer->fraction));
    $ansml->appendChild($doc->createElement('feedback', $answer->fraction));
    $ansml->appendChild($doc->createElement('tolerance', $answer->tolerance));
    
    return $ansml;
  }

  /*
   * make_server_xml 
   * @param doc      -- the document element (class DOMDocument) used to 
   *                    generate the nodes
   * @param serverid -- the id of the server to turn into xml
   * @return a XMLelement as created by $doc->createElement
   */
  function make_server_xml($doc, $serverid) {
    $server = get_record(ltj_serv_tbl(), "id", $serverid);
    if (!$server) {
      return NULL;
    }
    
    $serverml = $doc->createElement('server');
    $serverml->appendChild($doc->createElement('id', $server->id));
    $serverml->appendChild($doc->createElement('servername', 
					       $server->servername));
    $serverml->appendChild($doc->createElement('serverurl', 
					       $server->serverurl));
    
    return $serverml;
  }
} // End of question class
// Register this question type with the system.
question_register_questiontype(new ltjprocessed_qtype());
?>
