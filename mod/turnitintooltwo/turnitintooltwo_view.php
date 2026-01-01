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
 * @package   turnitintooltwo
 * @copyright 2010 iParadigms LLC
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/lib.php');

class turnitintooltwo_view {

    /**
     * Output the table of all the assignments in the class
     *
     * @param array $courses
     * @param array $tiiassignments
     * @global type $CFG
     * @global type $OUTPUT
     * @global type $USER
     * @return html output
     */
    public function output_all_assignment_list($courses, $tiiassignments) {
        global $CFG, $OUTPUT, $USER;

        $config = turnitintooltwo_admin_config();

        $table = new html_table();
        $table->id = "dataTable";
        $table->attributes['class'] = 'generaltable';
        $table->head = array(
            get_string('assignmentname', 'turnitintooltwo'),
            get_string('coursetitle', 'turnitintooltwo'),
            get_string('assignmentpart', 'turnitintooltwo'),
            get_string('submitted', 'turnitintooltwo'),
            get_string('maxmarks', 'turnitintooltwo'),
            get_string('assignmenttype', 'turnitintooltwo')
        );

        foreach ($tiiassignments as $tiiassignment) {
            $cm = get_coursemodule_from_id('turnitintooltwo', $tiiassignment->cm);
            $modulename = $cm->modname;
            $url = $CFG->wwwroot.'/mod/'.$modulename.'/view.php?id='.$cm->id;

            $row = array();
            $row[] = html_writer::link($url, $tiiassignment->tii_title);
            $row[] = $courses[$tiiassignment->courseid]->fullname;
            $row[] = $tiiassignment->partname;
            $row[] = $tiiassignment->submitted;
            $row[] = $tiiassignment->maxmarks;
            $row[] = get_string('turnitintooltwofile', 'turnitintooltwo');
            $table->data[] = $row;
        }

        $output = '';
        $output .= $OUTPUT->heading(get_string('allassignmentstitle', 'turnitintooltwo'), 2, 'main');
        $output .= $OUTPUT->box(html_writer::table($table), 'generalbox boxwidthwide boxaligncenter');

        return $output;
    }

    /**
     * Output the form to allow tutor to change various settings
     *
     * @param int $courseid
     * @param string $type
     * @global type $CFG
     * @global type $OUTPUT
     * @return html output
     */
    public function output_form($courseid, $type = "profile", $workflowcontext = "box") {
        global $CFG, $OUTPUT;

        $config = turnitintooltwo_admin_config();
        $configwarning = '';
        if (empty($config->accountid) || empty($config->apiurl) || empty($config->secretkey)) {
            $configwarning = html_writer::tag('div', get_string('configureerror', 'turnitintooltwo'), 
                array('class' => 'library_not_present_warning'));
        }

        $output = '';
        $output .= $configwarning;
        $output .= html_writer::tag('div', $this->output_title($type), array('class' => 'profileformtitle'));
        $output .= html_writer::tag('div', $this->output_settings_form($courseid, $type), array('class' => 'profileform'));

        return $output;
    }

    /**
     * Output the title for the form
     *
     * @param string $type
     * @return html title
     */
    public function output_title($type) {
        if ($type == "profile") {
            $title = get_string('editprofile', 'turnitintooltwo');
        } else {
            $title = get_string('settings', 'turnitintooltwo');
        }
        return $title;
    }

    /**
     * Output the settings form
     *
     * @param int $courseid
     * @param string $type
     * @global type $CFG
     * @global type $USER
     * @global type $DB
     * @return html form
     */
    public function output_settings_form($courseid, $type) {
        global $CFG, $USER, $DB;

        $output = '';
        $output .= html_writer::start_tag('form', array('action' => $CFG->wwwroot.'/mod/turnitintooltwo/view.php', 
            'method' => 'post', 'name' => 'turnitintooltwo_settings', 'id' => 'turnitintooltwo_settings'));
        $output .= html_writer::start_tag('fieldset', array('class' => 'clearfix'));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'cmd', 'value' => '_settings'));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'course', 'value' => $courseid));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'type', 'value' => $type));

        $elements = array();
        $elements[] = array('header', 'userdetails', get_string('userdetails', 'turnitintooltwo'));

        $elements[] = array('text', 'firstname', get_string('firstname', 'turnitintooltwo'), 
            array('size' => '25', 'maxlength' => '40'));
        $elements[] = array('text', 'lastname', get_string('lastname', 'turnitintooltwo'), 
            array('size' => '25', 'maxlength' => '40'));
        $elements[] = array('text', 'email', get_string('email', 'turnitintooltwo'), 
            array('size' => '25', 'maxlength' => '100'));

        $mform =& turnitintooltwo_moodle_form::factory('turnitintooltwo_form', $elements, 
            array('action' => $CFG->wwwroot.'/mod/turnitintooltwo/view.php', 'method' => 'post', 
            'name' => 'turnitintooltwo_settings', 'id' => 'turnitintooltwo_settings'));

        $output .= $mform->display();

        $output .= html_writer::end_tag('fieldset');
        $output .= html_writer::end_tag('form');

        return $output;
    }

    /**
     * Output the student's report with download functionality
     */
    public function output_student_report($submissiondata) {
        global $CFG, $OUTPUT;

        $output = '';
        $output .= html_writer::start_tag('div', array('class' => 'student-report-container'));
        $output .= html_writer::tag('h2', get_string('studentreport', 'turnitintooltwo'), array('class' => 'report-title'));

        // Display submission details
        $output .= html_writer::start_tag('div', array('class' => 'submission-details'));
        $output .= html_writer::tag('p', '<strong>' . get_string('submissiontitle', 'turnitintooltwo') . ':</strong> ' . 
            $submissiondata['title']);
        $output .= html_writer::tag('p', '<strong>' . get_string('submitteddate', 'turnitintooltwo') . ':</strong> ' . 
            $submissiondata['submitteddate']);
        $output .= html_writer::tag('p', '<strong>' . get_string('grademark', 'turnitintooltwo') . ':</strong> ' . 
            $submissiondata['grade']);
        $output .= html_writer::end_tag('div');

        // Add download buttons for both PDF and HTML versions if available
        $output .= html_writer::start_tag('div', array('class' => 'download-options'));
        
        // PDF download button (if PDF is available)
        if (!empty($submissiondata['pdf_path']) && file_exists($submissiondata['pdf_path'])) {
            $pdf_url = $CFG->wwwroot . '/mod/turnitintooltwo/download.php?type=pdf&submissionid=' . $submissiondata['id'];
            $output .= html_writer::tag('button', 
                get_string('downloadpdf', 'turnitintooltwo'), 
                array(
                    'class' => 'btn btn-primary download-report-btn',
                    'data-download-url' => $pdf_url,
                    'data-filename' => $submissiondata['title'] . '.pdf'
                )
            );
        }
        
        // HTML download button (if HTML is available)
        if (!empty($submissiondata['html_path']) && file_exists($submissiondata['html_path'])) {
            $html_url = $CFG->wwwroot . '/mod/turnitintooltwo/download.php?type=html&submissionid=' . $submissiondata['id'];
            $output .= html_writer::tag('button', 
                get_string('downloadhtml', 'turnitintooltwo'), 
                array(
                    'class' => 'btn btn-secondary download-report-btn',
                    'data-download-url' => $html_url,
                    'data-filename' => $submissiondata['title'] . '.html'
                )
            );
        }
        
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Output the instructor's grading interface
     */
    public function output_grading_interface($assignmentdata) {
        global $CFG, $OUTPUT;

        $output = '';
        $output .= html_writer::start_tag('div', array('class' => 'grading-interface'));
        $output .= html_writer::tag('h2', get_string('gradingtemplate', 'turnitintooltwo'), array('class' => 'grading-title'));

        // Display assignment details
        $output .= html_writer::start_tag('div', array('class' => 'assignment-details'));
        $output .= html_writer::tag('p', '<strong>' . get_string('assignmentname', 'turnitintooltwo') . ':</strong> ' . 
            $assignmentdata['name']);
        $output .= html_writer::tag('p', '<strong>' . get_string('course') . ':</strong> ' . 
            $assignmentdata['course']);
        $output .= html_writer::end_tag('div');

        // Add grading table
        $table = new html_table();
        $table->id = "gradingTable";
        $table->attributes['class'] = 'generaltable gradingtable';
        $table->head = array(
            get_string('studentname', 'turnitintooltwo'),
            get_string('submissiontitle', 'turnitintooltwo'),
            get_string('submitteddate', 'turnitintooltwo'),
            get_string('grademark', 'turnitintooltwo'),
            get_string('download', 'turnitintooltwo')
        );

        // Add sample rows (in real implementation, this would come from DB)
        foreach ($assignmentdata['submissions'] as $submission) {
            $row = array();
            $row[] = $submission['studentname'];
            $row[] = $submission['title'];
            $row[] = $submission['submitteddate'];
            $row[] = $submission['grade'];
            
            // Add download buttons for each submission
            $download_html = '';
            if (!empty($submission['pdf_path']) && file_exists($submission['pdf_path'])) {
                $pdf_url = $CFG->wwwroot . '/mod/turnitintooltwo/download.php?type=pdf&submissionid=' . $submission['id'];
                $download_html .= html_writer::tag('button', 
                    get_string('pdf', 'turnitintooltwo'), 
                    array(
                        'class' => 'btn btn-sm btn-primary download-report-btn',
                        'data-download-url' => $pdf_url,
                        'data-filename' => $submission['title'] . '.pdf'
                    )
                );
            }
            
            if (!empty($submission['html_path']) && file_exists($submission['html_path'])) {
                $html_url = $CFG->wwwroot . '/mod/turnitintooltwo/download.php?type=html&submissionid=' . $submission['id'];
                $download_html .= html_writer::tag('button', 
                    get_string('html', 'turnitintooltwo'), 
                    array(
                        'class' => 'btn btn-sm btn-secondary download-report-btn',
                        'data-download-url' => $html_url,
                        'data-filename' => $submission['title'] . '.html'
                    )
                );
            }
            
            $row[] = $download_html;
            $table->data[] = $row;
        }

        $output .= html_writer::table($table);
        $output .= html_writer::end_tag('div');

        return $output;
    }
}