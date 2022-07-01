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
 * GoToWebinar module view file
 *
 * @package mod_gotomeeting
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once($CFG->dirroot . '/mod/gotomeeting/locallib.php');

global $DB, $USER;
$id = required_param('id', PARAM_INT); // Course Module ID.
$action = optional_param('action', 'list', PARAM_TEXT);
$sesskey = optional_param('sesskey', '', PARAM_RAW);
require_login();
if (!is_siteadmin()) {
    throw new moodle_exception('nopermissions');
}
$gotomeetinglicence = $DB->get_record('gotomeeting_licence', array('id' => $id), '*', MUST_EXIST);
$enabled = false;
$disabled = false;
if ($action == 'disable' && confirm_sesskey($sesskey)) {

    if ($gotomeetinglicence && $gotomeetinglicence->active) {
        $gotomeetinglicence->active = 0;
        $gotomeetinglicence->timemodified = time();
        if ($DB->update_record('gotomeeting_licence', $gotomeetinglicence)) {
            $disabled = true;
        }
    } else {
        throw new moodle_exception('worongaction', 'gotomeeting');
    }
} else if ($action == 'enable' && confirm_sesskey($sesskey)) {
    if ($gotomeetinglicence && $gotomeetinglicence->active == 0) {
        $gotomeetinglicence->active = 1;
        $gotomeetinglicence->timemodified = time();
        if ($DB->update_record('gotomeeting_licence', $gotomeetinglicence)) {
            $enabled = true;
        }
    } else {
        throw new moodle_exception('worongaction', 'gotomeeting');
    }
}


$PAGE->set_url('/mod/gotomeeting/license.php', array('id' => $id, 'action' => $action));
$PAGE->set_title(get_string('license_title', 'mod_gotomeeting'));
$PAGE->set_heading(get_string('license_heading', 'mod_gotomeeting'));
echo $OUTPUT->header();
$link = $CFG->wwwroot . '/admin/settings.php?section=modsettinggotomeeting';
if ($enabled) {
    notice(get_string('license_enabled', 'mod_gotomeeting', $gotomeetinglicence->email), $link);
} else if ($disabled) {
    notice(get_string('license_disabled', 'mod_gotomeeting', $gotomeetinglicence->email), $link);
}


echo $OUTPUT->footer();
