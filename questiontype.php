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
//require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/remoteprocessed/question.php');


/**
 * The remoteprocessed question type.
 *
 * @copyright  2013 Leif Johnson (leif.t.johnson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_remoteprocessed extends question_type {

  /**
   * If your question type has a table that extends the question table, and
   * you want the base class to automatically save, backup and restore the extra fields,
   * override this method to return an array wherer the first element is the table name,
   * and the subsequent entries are the column names (apart from id and questionid).
   *
   * @return mixed array as above, or null to tell the base class to do nothing.
   */
  public function extra_question_fields() {
    return array('question_rmtproc', 'variables', 'imagecode', 'remotegrade', 
                 'serverid');
  }

    /**
     * If you use extra_question_fields, overload this function to return question id field name
     *  in case you table use another name for this column
     */
    public function questionid_column_name() {
        return 'question';
    }

    /**
     * If your question type has a table that extends the question_answers table,
     * make this method return an array wherer the first element is the table name,
     * and the subsequent entries are the column names (apart from id and answerid).
     *
     * @return mixed array as above, or null to tell the base class to do nothing.
     */
    public function extra_answer_fields() {
      return array('question_rmtproc_answers', 'tolerance');
    }

    /*
     * Export question to the Moodle XML format
     *
     * Export question using information from extra_question_fields function
     * If some of you fields contains id's you'll need to reimplement this
     */
    public function export_to_xml($question, qformat_xml $format, $extra=null) {
        $output = parent::export_to_xml($question, $format, $extra);
       
        // Export server information.

        $id = $format->xml_escape($question->options->server->id);
        $servername = $format->xml_escape($question->options->server->name);
        $serverurl = $format->xml_escape($question->options->server->url);
        $output .= "    <server>\n";
        $output .= "        <id>{$id}</id>\n";
        $output .= "        <servername>{$servername}</servername>\n";
        $output .= "        <serverurl>{$serverurl}</serverurl>\n";
        $output .= "    </server>\n";

        return $output;
    }

  public function convert_old_xml_format($data, qformat_xml $format) {
    if (!array_key_exists('remoteprocessed', $data['#'])) {
      return $data;
    }

    $remoteprocessed = array_shift($data['#']['remoteprocessed']);
    $value_map = array(
      'answer'      => 'answers',
      'server'      => 'server',
      'imagecode'   => 'imagecode',
      'variables'   => 'variables',
      'remotegrade' => 'remotegrade',
      );
    foreach ($value_map as $key => $value) {
      $data['#'][$key] = 
        $format->getpath($remoteprocessed, array('#', $value), '');
    }

    return $data;
  }

  public function find_or_insert_server($id, $name, $url) {
    GLOBAL $DB;

    // See if there is an existing record matching this url, if so, return that.
    $server = $DB->get_record("question_rmtproc_servers",
      array('url' => $url));
    if ($server) {
      return $server;
    }

    $server = (object) array("name" => "IMPORTED " . $name, 
                             "url"  => $url);
    $server->id = $DB->insert_record("question_rmtproc_servers", $server);

    return $server;
  }

  public function import_from_xml($data, $question, qformat_xml $format, 
    $extra=null) {
    // Convert old xml format to new xml format.
    error_log('import_from_xml');
    error_log(implode(',', array_keys($data['#'])));

    if (array_key_exists('remoteprocessed', $data['#'])) {
      $data = $this->convert_old_xml_format($data, $format);
    }

    // Now process the question.
    $qo = parent::import_from_xml($data, $question, $format, $extra);

    // Find the server field.
    $server = $data['#']['server'];

    $serverid = 
      $format->getpath($server, array('#', 'id', 0, '#'), '');
    $servername = 
      $format->getpath($server, array('#', 'servername', 0, '#'), '');
    $serverurl = 
      $format->getpath($server, array('#', 'serverurl', 0, '#'), '');

    $server = $this->find_or_insert_server($serverid, $servername, $serverurl);

    $qo->serverid = $server->id;
    $qo->server = $server;

    return $qo;
  }

  public function move_files($questionid, $oldcontextid, $newcontextid) {
    parent::move_files($questionid, $oldcontextid, $newcontextid);
    $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);
  }

  protected function delete_files($questionid, $contextid) {
    parent::delete_files($questionid, $contextid);
    $this->delete_files_in_hints($questionid, $contextid);
  }
    
    public function delete_question($questionid, $contextid) {
      global $DB;
      
      $question_array = array('question' => $questionid);
      $DB->delete_records('question_rmtproc', $question_array);
      $DB->delete_records('question_rmtproc_answers', $question_array);
      $DB->delete_records('question_rmtproc_attempt', $question_array);

      parent::delete_question($questionid, $contextid);
    }

    public function save_question_options($question) {
      global $DB;
      
     //  $context = $question->context;

     //  $this->save_hints($question);
      
     //  $update = true;
     //  $options = $DB->get_record("question_rmtproc", 
				 // array("question" => $question->id));
     //  if (!$options) {
	    //    $update = false;
	    //    $options = qtype_remoteprocessed_question::default_options();
	    //    $options->question = $question->id;
     //  }
      
     //  foreach (qtype_remoteprocessed_question::$options_keys as $key) {
	    //  if (isset($question->{$key})) {
	    //    $options->{$key} = $question->{$key};
	    //  }
     //  }

     //  if ($update) {
	    //    $DB->update_record('question_rmtproc', $options);
     //  } else {
	    //    $DB->insert_record('question_rmtproc', $options);
     //  }
      // Use parent function, it automatically takes care of the extra question 
      // fields.
      parent::save_question_options($question);

      // Now save the question answers.  Answer data is mixed between two 
      // tables.
      //
      // 1. Get the old answers and answer supplemental data.
      // 2. Loop through the answers we have to save, update or add each 
      //    element as necessary.
      // 3. Any answers and answer supplemental data left over is deleted.
      if (isset($question->answer) && !isset($question->answers)) {
	       $question->answers = $question->answer;
      }

      $oldanswers = $DB->get_records('question_answers', 
				     array('question' => $question->id),
				     'id ASC');
      $oldremoteanswers = $DB->get_records('question_rmtproc_answers',
					   array('question' => $question->id),
					   'answerid ASC');
      
      if (!isset($question->answers)) {
	       $question->answers = array();
      }

      foreach ($question->answers as $key => $answerdata) {
	       if (is_array($answerdata)) {
	         $answerdata = $answerdata['text'];
	       }

	     $answerdata = trim($answerdata);

	     if ($answerdata == '') {
	       continue;
	     }

	     if (!empty($oldanswers)) {
	       $answer = array_shift($oldanswers);
	     } else {
	       $answer = qtype_remoteprocessed_question::default_answer();
	       $answer->id = $DB->insert_record('question_answers', $answer);
	     }
	
	     $answer->answer = $answerdata;
	     $answer->question = $question->id;
	     $answer->fraction = $question->fraction[$key];
	     $answer->feedback = 
	       $this->import_or_save_files($question->feedback[$key],
				      $context, 'question', 'answerfeedback', 
				      $answer->id);
	     $answer->feedbackformat = $question->feedback[$key]['format'];
	
	     $DB->update_record("question_answers", $answer);

	     // Save the extra answer data.
	     $rp_answer = array_shift($oldremoteanswers);
	     if (!$rp_answer) {
	       $rp_answer = 
	         qtype_remoteprocessed_question::default_remoteprocessed_answer();
	     } 
	
	     $rp_answer->question  = $question->id;
	     $rp_answer->answerid  = $answer->id;
	     $rp_answer->tolerance = trim($question->tolerance[$key]);
	
	     if (isset($rp_answer->id)) {
	       $DB->update_record("question_rmtproc_answers", $rp_answer);
	     } else {
	       $rp_answer->id = 
	       $DB->insert_record("question_rmtproc_answers", $rp_answer);
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
	    $DB->delete_records("question_rmtproc_answers",  
			     array("id" => $ora->id));
	   }
    }
  }
    
    public function get_question_options($question) {
      global $DB;

      // Most of the work is done by the parent function.
      $value = parent::get_question_options($question);
      if ($value === false) {
        return false;
      }

      // We need to load the server info, the parent function
      // only finds the serverid.
      $question->options->server = 
        $DB->get_record("question_rmtproc_servers", 
          array("id" => $question->options->serverid));

      return true;
    }

    protected function initialise_question_instance(
      question_definition $question, $questiondata) {
      parent::initialise_question_instance($question, $questiondata);
      $question->options = $questiondata->options;
    }

    public function get_random_guess_score($questiondata) {
        // TODO.
        return 0;
    }

    public function get_possible_responses($questiondata) {
        // TODO.
        return array();
    }

    /* 
     * Get a menu of available remote processing servers.
     */
    public static function get_remote_processed_servers_menu() {
      GLOBAL $DB;
      $menu = $DB->get_records_menu('question_rmtproc_servers',
				    null,
				    'id ASC',
				    'id,name');
      return $menu;
    }
}
