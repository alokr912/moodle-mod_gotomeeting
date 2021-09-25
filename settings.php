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

    $licences  = $DB->get_records('gotomeeting_licence' );
    foreach($licences as $licence){
        $settings->add(new admin_setting_description($licence->id,$licence->email,'Active'));
        
    }
    $url = $CFG->wwwroot . '/mod/gotomeeting/setup.php';
    
    $url = htmlentities($url, ENT_COMPAT, 'UTF-8');
    $options = 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=0,width=700,height=300';
    $str = '<center><input type="button" onclick="window.open(\'' . $url . '\', \'\', \'' . $options . '\');" value="' .
            get_string('addlicence', 'gotomeeting') . '" /></center>';
    $settings->add(new admin_setting_heading('adobeconnect_test', '', $str));
}

