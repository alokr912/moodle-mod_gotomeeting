<?php

/**
 * GoToMeeting module configtest  file
 *
 * @package mod_gotomeeting
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('./classes/gotooauth.class.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url($CFG->wwwroot . '/mod/gotomeeting/configtest.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_heading('GoToMeeting config test report');
$PAGE->set_title('GoToMeeting config test report');
require_login();

if (!is_siteadmin()) {
    print_error('nopermissions', 'gotomeeting', '', null);
}

$gotomeetingconfig = get_config(GoToOAuth::PLUGIN_NAME);

$goToAuth = new GoToOAuth();
$status = $goToAuth->getSetupStatus();
if ($status) {
    echo $OUTPUT->header();
    echo html_writer::div('GoToWebinar setup status ', 'alert alert-info');
    echo html_writer::div('GoToWebinar config  organizer email '.$status->email, 'alert alert-success');
    echo html_writer::div('GoToWebinar config organizer firstName '.$status->firstName, 'alert alert-success');
    echo html_writer::div('GoToWebinar config  organizer lastName '. $status->lastName, 'alert alert-success');
    echo html_writer::div('GoToWebinar config  organizer key '.$status->organizer_key, 'alert alert-success');
     echo html_writer::div('GoToWebinar config  account key '.$status->account_key, 'alert alert-success');
  
                
    echo $OUTPUT->footer();
} else if (isset($gotomeetingconfig->consumer_key) && $gotomeetingconfig->consumer_key != '' && isset($gotomeetingconfig->consumer_secret) && $gotomeetingconfig->consumer_secret != '') {
    
    $redirect_url =  $CFG->wwwroot.'/mod/gotomeeting/oauthCallback.php';
    $url =  GoToOAuth::BASE_URL."/oauth/v2/authorize?client_id=$gotomeetingconfig->consumer_key&response_type=code&redirect_uri=$redirect_url";

    redirect($url);
} else {

    echo $OUTPUT->header();

    echo html_writer::div('GoToMeeting config validation ', 'alert alert-info');

    $consumerKey = trim($gotomeetingconfig->consumer_key);
    if (isset($gotomeetingconfig->consumer_key) && $gotomeetingconfig->consumer_key == '') {


        echo html_writer::div('GoToMeeting consumer key missing', 'alert alert-danger');
    }
    if (isset($gotomeetingconfig->consumer_secret) && $gotomeetingconfig->consumer_secret == '') {

        echo html_writer::div('GoToMeeting consumer secert missing', 'alert alert-danger');
    }



    echo $OUTPUT->footer();
}