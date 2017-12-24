<?php
/**
 * GoToMeeting module configtest  file
 *
 * @package mod_gotomeeting
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once $CFG->dirroot . '/mod/gotomeeting/lib/OSD.php';

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url($CFG->wwwroot . '/mod/gotomeeting/configtest.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_heading('GoToMeeting config test report');
$PAGE->set_title('GoToMeeting config test report');
 require_login();      
echo $OUTPUT->header();
if (!is_siteadmin()) {
    print_error('nopermissions', 'gotomeeting', '', null);
}

$gotomeetingconfig = get_config('gotomeeting');

echo html_writer::div('GoToMeeting config validation ', 'alert alert-info');
$validconsumerkey = true;
$validuserid = true;
$validpassword = true;
if (isset($gotomeetingconfig->consumer_key) && $gotomeetingconfig->consumer_key == '') {
    $validconsumerkey = false;
    echo html_writer::div('GoToMeeting consumer key missing', 'alert alert-danger');
}
if (isset($gotomeetingconfig->userid) && $gotomeetingconfig->userid == '') {
    $validuserid = false;
    echo html_writer::div('GoToMeeting userid missing', 'alert alert-danger');
}
if (isset($gotomeetingconfig->password) && $gotomeetingconfig->password == '') {
    $validpassword = false;
    echo html_writer::div('GoToMeeting password missing', 'alert alert-danger');
}
if ($validconsumerkey && $validuserid && $validpassword) {
   
    OSD::setup(trim($gotomeetingconfig->consumer_key));
    if (OSD::authenticate_with_password(trim($gotomeetingconfig->userid), trim($gotomeetingconfig->password))) {
        $auth = OSD::$oauth;
        $content = 'Authentication successfull with '
                . '  organizer_key:  ' . $auth->organizer_key;
        echo html_writer::div($content, 'alert alert-success');
    } else {
        echo html_writer::div(OSD::$last_response->body, 'alert alert-danger');
    }
}

echo $OUTPUT->footer();
