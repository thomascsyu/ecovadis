<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later treatment.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Prints a particular instance of turnitintooltwo
 *
 * @package   turnitintooltwo
 * @copyright 2010 iParadigms LLC
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

$id = required_param('id', PARAM_INT); // Course Module ID.
$do = optional_param('do', 'submissions', PARAM_ALPHA);

$cm = get_coursemodule_from_id('turnitintooltwo', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$turnitintooltwo = $DB->get_record('turnitintooltwo', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/turnitintooltwo:view', $context);

// Initialize $PAGE
$PAGE->set_url('/mod/turnitintooltwo/view.php', array('id' => $cm->id, 'do' => $do));
$PAGE->set_title(format_string($turnitintooltwo->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Include the JavaScript for download functionality
$PAGE->requires->jquery();
$PAGE->requires->js('/mod/turnitintooltwo/jquery/turnitintooltwo.js');

// Generate the output
$turnitintooltwoview = new turnitintooltwo_view();

switch ($do) {
    case "submissions":
    default:
        // Get submissions for this assignment
        $submissions = $DB->get_records('turnitintooltwo_submissions', array('turnitintooltwoid' => $turnitintooltwo->id));
        
        // Prepare submission data for display
        $submissionsdata = array();
        foreach ($submissions as $submission) {
            $submissionsdata[] = array(
                'id' => $submission->id,
                'title' => $submission->submission_title,
                'studentname' => fullname($DB->get_record('user', array('id' => $submission->userid))),
                'submitteddate' => userdate($submission->submission_modified),
                'grade' => !is_null($submission->submission_grade) ? $submission->submission_grade . '/' . $turnitintooltwo->maxmarks : 'Not Graded',
                'pdf_path' => $submission->pdf_path,
                'html_path' => $submission->html_path
            );
        }
        
        // Prepare assignment data for grading interface
        $assignmentdata = array(
            'name' => $turnitintooltwo->name,
            'course' => $course->fullname,
            'submissions' => $submissionsdata
        );
        
        // Output the grading interface
        echo $turnitintooltwoview->output_grading_interface($assignmentdata);
        break;
}

// Finish the page
echo $OUTPUT->footer();