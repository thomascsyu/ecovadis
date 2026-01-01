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
 * Download handler for PDF and HTML reports
 *
 * @package   turnitintooltwo
 * @copyright 2010 iParadigms LLC
 */

require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Require login
require_login();

// Get parameters
$type = required_param('type', PARAM_ALPHA);
$submissionid = required_param('submissionid', PARAM_INT);

// Validate the submission belongs to the current user or they have appropriate permissions
$submission = $DB->get_record('turnitintooltwo_submissions', array('id' => $submissionid), '*', MUST_EXIST);

// Check permissions - either user owns submission or has grading capability
$cm = get_coursemodule_from_instance('turnitintooltwo', $submission->turnitintooltwoid);
$context = context_module::instance($cm->id);

if ($USER->id != $submission->userid && !has_capability('mod/turnitintooltwo:grade', $context)) {
    print_error('nopermissions', 'error');
}

// Determine file path based on type and submission data
$file_path = '';
if ($type === 'pdf' && !empty($submission->pdf_path) && file_exists($submission->pdf_path)) {
    $file_path = $submission->pdf_path;
    $content_type = 'application/pdf';
    $download_filename = $submission->submission_title . '.pdf';
} elseif ($type === 'html' && !empty($submission->html_path) && file_exists($submission->html_path)) {
    $file_path = $submission->html_path;
    $content_type = 'text/html';
    $download_filename = $submission->submission_title . '.html';
} else {
    print_error('filenotfound', 'error');
}

// Security check - ensure file is within allowed directories
$allowed_paths = array($CFG->dataroot . '/temp/', $CFG->dataroot . '/turnitintooltwo/', $CFG->dataroot . '/temp/turnitintooltwo_reports/');
$is_allowed_path = false;
foreach ($allowed_paths as $allowed_path) {
    if (strpos(realpath($file_path), realpath($allowed_path)) === 0) {
        $is_allowed_path = true;
        break;
    }
}

if (!$is_allowed_path) {
    print_error('accessdenied', 'error');
}

// Verify file exists and is readable
if (!file_exists($file_path) || !is_readable($file_path)) {
    print_error('filenotfound', 'error');
}

// Log the download
$event = \mod_turnitintooltwo\event\report_downloaded::create(array(
    'objectid' => $submissionid,
    'context' => $context,
    'other' => array('type' => $type)
));
$event->add_record_snapshot('turnitintooltwo_submissions', $submission);
$event->trigger();

// Set headers for download
header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . basename($download_filename) . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Expires: 0');

// Clear output buffer and send file
if (ob_get_level()) {
    ob_end_clean();
}

readfile($file_path);

// Exit to prevent any additional output
exit;