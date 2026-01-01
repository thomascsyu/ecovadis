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
 * Library of functions, classes, etc. for the turnitintooltwo module
 *
 * @package   turnitintooltwo
 * @copyright 2010 iParadigms LLC
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $turnitintooltwo An object from the form in mod_form.php
 * @return int The id of the newly inserted turnitintooltwo record
 */
function turnitintooltwo_add_instance($turnitintooltwo) {
    global $DB;

    $turnitintooltwo->timecreated = time();
    $turnitintooltwo->timemodified = time();

    return $DB->insert_record('turnitintooltwo', $turnitintooltwo);
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $turnitintooltwo An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function turnitintooltwo_update_instance($turnitintooltwo) {
    global $DB;

    $turnitintooltwo->timemodified = time();
    $turnitintooltwo->id = $turnitintooltwo->instance;

    return $DB->update_record('turnitintooltwo', $turnitintooltwo);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function turnitintooltwo_delete_instance($id) {
    global $DB;

    if (! $turnitintooltwo = $DB->get_record('turnitintooltwo', array('id' => $id))) {
        return false;
    }

    $result = true;

    // Delete any dependent records here.
    if ($submissions = $DB->get_records('turnitintooltwo_submissions', array('turnitintooltwoid' => $turnitintooltwo->id))) {
        foreach ($submissions as $submission) {
            turnitintooltwo_delete_submission($submission->id);
        }
    }

    if (!$DB->delete_records('turnitintooltwo', array('id' => $turnitintooltwo->id))) {
        $result = false;
    }

    return $result;
}

/**
 * Delete a submission and associated files
 */
function turnitintooltwo_delete_submission($submissionid) {
    global $DB;

    $submission = $DB->get_record('turnitintooltwo_submissions', array('id' => $submissionid));
    if (!$submission) {
        return false;
    }

    // Delete associated files if they exist
    if (!empty($submission->pdf_path) && file_exists($submission->pdf_path)) {
        unlink($submission->pdf_path);
    }
    
    if (!empty($submission->html_path) && file_exists($submission->html_path)) {
        unlink($submission->html_path);
    }

    return $DB->delete_records('turnitintooltwo_submissions', array('id' => $submissionid));
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $turnitintooltwo
 * @return object|null
 */
function turnitintooltwo_user_outline($course, $user, $mod, $turnitintooltwo) {
    $return = new stdClass();
    $return->time = '';
    $return->info = '';
    $return->comment = '';
    return $return;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $turnitintooltwo
 * @return boolean
 */
function turnitintooltwo_user_complete($course, $user, $mod, $turnitintooltwo) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in turnitintooltwo activities and print it out.
 *
 * @param object $course
 * @param bool $viewfullnames
 * @param int $timestart
 * @return boolean
 */
function turnitintooltwo_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  // True if anything was printed, otherwise false
}

/**
 * Returns all other caps used in the module
 *
 * @return array
 */
function turnitintooltwo_get_extra_capabilities() {
    return array();
}

/**
 * Define the capabilities for the turnitintooltwo module
 */
function turnitintooltwo_get_view_actions() {
    return array('view', 'view all');
}

/**
 * Define the capabilities for the turnitintooltwo module
 */
function turnitintooltwo_get_post_actions() {
    return array('add', 'update');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function turnitintooltwo_reset_userdata($data) {
    return array();
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the turnitintooltwo module.
 */
function turnitintooltwo_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'turnitintooltwoheader', get_string('modulename', 'turnitintooltwo'));
}

/**
 * Course reset form defaults.
 */
function turnitintooltwo_reset_course_form_defaults($course) {
    return array();
}

/**
 * Generate a report for a submission (PDF or HTML as fallback)
 */
function turnitintooltwo_generate_submission_report($submissionid) {
    global $DB;
    
    require_once(__DIR__.'/classes/report_generator.php');
    
    $submission = $DB->get_record('turnitintooltwo_submissions', array('id' => $submissionid), '*', MUST_EXIST);
    $assignment = $DB->get_record('turnitintooltwo', array('id' => $submission->turnitintooltwoid), '*', MUST_EXIST);
    
    // Get questions and answers for this submission if they exist
    $questions = array(); // This would come from your question/answer tables
    $answers = array();   // This would come from your question/answer tables
    
    // Prepare submission data for the report
    $submissiondata = array(
        'id' => $submission->id,
        'title' => $assignment->name,
        'studentname' => fullname($DB->get_record('user', array('id' => $submission->userid))),
        'submitteddate' => userdate($submission->submission_modified),
        'grade' => !is_null($submission->grade) ? $submission->grade . '/' . $assignment->maxmarks : 'Not Graded',
        'course' => $DB->get_record('course', array('id' => $assignment->course))->fullname
    );
    
    // Generate the report
    $report = turnitintooltwo_report_generator::generate_report($submissiondata, $questions, $answers);
    
    // Update the submission record with the report paths
    $update_data = new stdClass();
    $update_data->id = $submission->id;
    if (!empty($report['pdf_path'])) {
        $update_data->pdf_path = $report['pdf_path'];
    }
    if (!empty($report['html_path'])) {
        $update_data->html_path = $report['html_path'];
    }
    
    $DB->update_record('turnitintooltwo_submissions', $update_data);
    
    return $report;
}