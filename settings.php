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
 * GoToMeeting module global settings file
 *
 * @package mod_gotomeeting
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    global $DB;

    $name = 'gotomeeting/consumer_key';
    $visiblename = get_string('gtm_consumer_key', 'gotomeeting');
    $description = get_string('gtm_consumer_key_desc', 'gotomeeting');
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, '', PARAM_RAW, 50));

    $name = 'gotomeeting/consumer_secret';
    $visiblename = get_string('gtm_consumer_secret', 'gotomeeting');
    $description = get_string('gtm_consumer_secret_desc', 'gotomeeting');
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, '', PARAM_RAW, 50));

    $licences = $DB->get_records('gotomeeting_licence');

    $actionshtml = html_writer::start_div('container');

    foreach ($licences as $licence) {
        if ($licence->active) {

            $class = "btn-outline-danger";
            $url = new moodle_url('/mod/gotomeeting/license.php',
                    array('id' => $licence->id, 'action' => 'disable', 'sesskey' => sesskey()));

            $actionshtml .= html_writer::start_div('row');
            $actionshtml .= html_writer::start_div('col-md-12');
            $actionshtml .= html_writer::link($url, 'Disable ' . $licence->email, array('class' => 'btn btn-outline-danger'));
            $actionshtml .= html_writer::end_div();
            $actionshtml .= html_writer::end_div();
        } else {
            $class = "btn-secondary";
            $url = new moodle_url('/mod/gotomeeting/license.php',
                    array('id' => $licence->id, 'action' => 'enable', 'sesskey' => sesskey()));
            $actionshtml .= html_writer::start_div('row');
            $actionshtml .= html_writer::start_div('col-md-12');
            $actionshtml .= html_writer::link($url, 'Enable ' . $licence->email, array('class' => 'btn btn-secondary'));
            $actionshtml .= html_writer::end_div();
            $actionshtml .= html_writer::end_div();
        }
    }
    $class = "btn-primary";
    $url = new moodle_url('/mod/gotomeeting/setup.php', array('sesskey' => sesskey()));
    $actionshtml .= html_writer::start_div('row mt-5 mb-5');
    $actionshtml .= html_writer::start_div('col-md-12');
    $actionshtml .= html_writer::link($url, get_string('addlicence', 'mod_gotomeeting'), array('class' => 'btn btn-secondary'));
    $actionshtml .= html_writer::end_div();
    $actionshtml .= html_writer::end_div();
    $actionshtml .= html_writer::end_div();
    $settings->add(new admin_setting_heading('gotomeeting_license', '', $actionshtml));

    $url = $CFG->wwwroot . '/mod/gotomeeting/setup.php';

    $url = htmlentities($url, ENT_COMPAT, 'UTF-8');
    $options = 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=0,width=700,height=300';
    $str = '<center><input type="button" onclick="window.open(\'' . $url . '\', \'\', \'' . $options . '\');" value="' .
            get_string('addlicence', 'gotomeeting') . '" /></center>';
}

