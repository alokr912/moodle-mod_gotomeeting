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
 * GoToMeeting module configtest  file
 *
 * @package mod_gotomeeting
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('./classes/GotoOAuth.php');

require_login();
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url($CFG->wwwroot . '/mod/gotomeeting/setup.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_heading(get_string('setup_heading', 'gotomeeting'));
$PAGE->set_title(get_string('setup_title', 'gotomeeting'));

if (!is_siteadmin()) {
    throw new moodle_exception('nopermissions', 'gotomeeting');
}

$gotomeetingconfig = get_config(mod_gotomeeting\GoToOAuth::PLUGIN_NAME);

$gotoauth = new mod_gotomeeting\GoToOAuth(null);
if (!empty($gotomeetingconfig->consumer_key) && !empty($gotomeetingconfig->consumer_secret)) {
    $consumerkey = trim($gotomeetingconfig->consumer_key);
    $redirecturl = $CFG->wwwroot . '/mod/gotomeeting/oauthCallback.php';
    $url = mod_gotomeeting\GoToOAuth::BASE_URL . "/oauth/v2/authorize?client_id="
            . "$consumerkey&response_type=code&redirect_uri=$redirecturl";

    redirect($url);
} else {

    echo $OUTPUT->header();

    echo html_writer::div(get_string('setup_akey', 'gotomeeting'), 'alert alert-info');

    if (isset($gotomeetingconfig->consumer_key) && $gotomeetingconfig->consumer_key == '') {

        echo html_writer::div(get_string('setup_key_missing', 'gotomeeting'), 'alert alert-danger');
    }
    if (isset($gotomeetingconfig->consumer_secret) && $gotomeetingconfig->consumer_secret == '') {

        echo html_writer::div(get_string('setup_secret_missing', 'gotomeeting'), 'alert alert-danger');
    }

    echo $OUTPUT->footer();
}
