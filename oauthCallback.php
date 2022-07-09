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
 * GoToMeeting module OAuthCallbak  file
 *
 * @package mod_gotomeeting
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');

require_once('./classes/GotoOAuth.php');

$code = required_param('code', PARAM_RAW);

require_admin();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url($CFG->wwwroot . '/mod/gotomeeting/oauthCallback.php', array('code' => $code)));
$PAGE->set_pagelayout('admin');

$gotomeeting = new mod_gotomeeting\GoToOAuth(null);
$result = $gotomeeting->getaccesstokenwithcode($code);

$PAGE->set_title(get_string('pluginname', 'gotomeeting'));
$PAGE->set_heading(get_string('setup', 'gotomeeting'));
echo $OUTPUT->header();

if ($result) {
    echo html_writer::div(get_string('setupcomplete', 'gotomeeting'), 'alert alert-success');
} else {
    echo html_writer::div(get_string('setuperror', 'gotomeeting'), 'alert alert-danger');
}
echo $OUTPUT->footer();

