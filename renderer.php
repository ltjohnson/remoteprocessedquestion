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
 * remoteprocessed question renderer class.
 *
 * @package    qtype
 * @subpackage remoteprocessed
 * @copyright  2013 Leif Johnson (leif.t.johnson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Generates the output for remoteprocessed questions.
 *
 * @copyright  2013 Leif Johnson (leif.t.johnson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_remoteprocessed_renderer extends qtype_renderer {
    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {

        $question = $qa->get_question();

        $questiontext = $question->format_questiontext($qa);

        if ($question->image != "") {
            $questiontext = base64_png_img_tag($question->image) . "<br />" . 
                $questiontext;
        }

        // Answer field.
        $answer = $qa->get_last_qt_var('answer');
        $inputname = $qa->get_qt_field_name('answer');
        $inputattributes = array(
            'type' => 'text',
            'name' => $inputname,
            'value' => $answer,
            'id' => $inputname,
            'size' => 80,
        );

        if ($answer) {
            $inputattributes['value'] = $answer;
        }

        if ($options->readonly){
            $inputattributes['readonly'] = 'readonly';
        }

        // TODO: feedback images.
        $input = html_writer::empty_tag('input', $inputattributes);

        $result = html_writer::tag('div', $questiontext, array('class' => 'qtext'));
        $result .= html_writer::tag('div', $input, array('class' => 'answer'));

        /* if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error(array('answer' => $currentanswer)),
                    array('class' => 'validationerror'));
        }*/
        return $result;
    }

    public function specific_feedback(question_attempt $qa) {
        $question = $qa->get_question();
        $response = array('answer' => $qa->get_last_qt_var('answer'));
        // TODO, cache grading to avoid extra grading calls.
        $answer = $question->get_matching_answer($response);

        $feedback = '';

        if ($answer && $answer->feedback) {
            $feedback = $question->format_text($answer->feedback, $answer->feedbackformat,
                    $qa, 'question', 'answerfeedback', $answer->id);
        }
        
        return $feedback;
    }

    public function correct_response(question_attempt $qa) {
        // TODO.
        return '';
    }
}
