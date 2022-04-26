<?php

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
$id = required_param('id', PARAM_INT); // Course Module ID
$action = optional_param('action', 'list', PARAM_TEXT);
$sesskey = optional_param('sesskey', '', PARAM_RAW);
require_login();
if (!is_siteadmin()) {
    print_error('nopermissions', 'gotomeeting');
}
$gotomeeting_licence = $DB->get_record('gotomeeting_licence', array('id' => $id), '*', MUST_EXIST);
$enabled = false;
$disabled = false;
if ($action == 'disable' && confirm_sesskey($sesskey)) {

    if ($gotomeeting_licence && $gotomeeting_licence->active) {
        $gotomeeting_licence->active = 0;
        $gotomeeting_licence->timemodified = time();
        if ($DB->update_record('gotomeeting_licence', $gotomeeting_licence)) {
            $disabled = true;
        }
    } else {
        print_error('worongaction', 'gotomeeting');
    }
} else if ($action == 'enable' && confirm_sesskey($sesskey)) {
    if ($gotomeeting_licence && $gotomeeting_licence->active == 0) {
        $gotomeeting_licence->active = 1;
        $gotomeeting_licence->timemodified = time();
        if ($DB->update_record('gotomeeting_licence', $gotomeeting_licence)) {
            $enabled = true;
        }
    } else {
        print_error('worongaction', 'gotomeeting');
    }
}


$PAGE->set_url('/mod/gotomeeting/license.php', array('id' => $id, 'action' => $action));
$PAGE->set_title(get_string('license_title', 'mod_gotomeeting'));
$PAGE->set_heading(get_string('license_heading', 'mod_gotomeeting'));
echo $OUTPUT->header();
$link = $CFG->wwwroot . '/admin/settings.php?section=modsettinggotomeeting';
if ($enabled) {
    notice(get_string('license_enabled', 'mod_gotomeeting', $gotomeeting_licence->email), $link);
} else if ($disabled) {
    notice(get_string('license_disabled', 'mod_gotomeeting', $gotomeeting_licence->email), $link);
}


echo $OUTPUT->footer();
