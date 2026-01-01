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
 * Display all turnitintooltwo instances in a course
 *
 * @package   turnitintooltwo
 * @copyright 2010 iParadigms LLC
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

$id = required_param('id', PARAM_INT); // Course ID.

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_course_login($course);

// Initialize $PAGE
$PAGE->set_url('/mod/turnitintooltwo/index.php', array('id' => $id));
$PAGE->set_title(get_string('modulenameplural', 'turnitintooltwo'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'turnitintooltwo'));

// Get all instances of turnitintooltwo in this course
$turnitintooltwos = get_all_instances_in_course('turnitintooltwo', $course);

if (empty($turnitintooltwos)) {
    notice(get_string('noturnitintooltwos', 'turnitintooltwo'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($course->format == 'weeks') {
    $table->head = array(get_string('week'), get_string('name'));
    $table->align = array('center', 'left');
} else if ($course->format == 'topics') {
    $table->head = array(get_string('topic'), get_string('name'));
    $table->align = array('center', 'left', 'left', 'left');
} else {
    $table->head = array(get_string('name'));
    $table->align = array('left', 'left', 'left');
}

foreach ($turnitintooltwos as $turnitintooltwo) {
    $linkparams = array('id' => $turnitintooltwo->coursemodule);
    $link = html_writer::link(new moodle_url('/mod/turnitintooltwo/view.php', $linkparams), format_string($turnitintooltwo->name));
    
    if ($course->format == 'weeks' || $course->format == 'topics') {
        $table->data[] = array($turnitintooltwo->section, $link);
    } else {
        $table->data[] = array($link);
    }
}

echo html_writer::table($table);
echo $OUTPUT->footer();