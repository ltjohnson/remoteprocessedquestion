<?php
/**
 * Unit tests for this question type.
 *
 * @copyright &copy; 2008 University of Minnesota, Twin Cities
 * @author Leif Johnson; leif.t.johnson@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ltjprocessed
 *//** */
    
require_once(dirname(__FILE__) . '/../../../../config.php');

global $CFG;
require_once($CFG->libdir . '/simpletestlib.php');
require_once($CFG->dirroot . '/question/type/ltj_processed/questiontype.php');

class ltj_processed_qtype_test extends UnitTestCase {
    var $qtype;
    
    function setUp() {
        $this->qtype = new ltj_processed_qtype();
    }
    
    function tearDown() {
        $this->qtype = null;    
    }

    function test_name() {
        $this->assertEqual($this->qtype->name(), 'ltj_processed');
    }
    
    // TODO write unit tests for the other methods of the question type class.
}

?>
