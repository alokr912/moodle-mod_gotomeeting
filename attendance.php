<?php
// This file is part of the GoToMeeting plugin for Moodle - http://moodle.org/
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
 * GoToMeeting module view
 * @package mod_gotomeeting
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once('lib.php');
require_once($CFG->dirroot . '/mod/gotomeeting/locallib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir.'/tablelib.php');
global $DB, $USER;
$id = required_param('id', PARAM_INT); // Course Module ID.
 
if ($id) {
    if (!$cm = get_coursemodule_from_id('gotomeeting', $id)) {
        throw new coding_exception('invalidcoursemodule');
    }
    $gotomeeting = $DB->get_record('gotomeeting', ['id' => $cm->instance], '*', MUST_EXIST);
}
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/gotomeeting:view', $context);
$PAGE->requires->js_init_call('M.mod_gotomeeting.attendance', get_gotomeeting_attendance_data($gotomeeting));
$PAGE->set_url('/mod/gotomeeting/attendance.php', ['id' => $cm->id]);
$PAGE->set_title($course->shortname . ': ' . $gotomeeting->name);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulename', 'gotomeeting') . ' : ' . $gotomeeting->name);

echo html_writer::div("",null,array('id'=>'attendance'));

echo $OUTPUT->footer();
