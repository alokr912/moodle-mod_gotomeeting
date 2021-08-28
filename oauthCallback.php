<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once('../../config.php');

require_once('./classes/gotooauth.class.php');
$code = required_param('code', PARAM_RAW);

$goToMeeting = new GoToOAuth();
$result = $goToMeeting->getAccessTokenWithCode($code);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url($CFG->wwwroot . '/mod/gotomeeting/oauthCallback.php'));
echo $OUTPUT->header();
if($result){
        echo html_writer::div('GoToMeeting consumer is complete', 'alert alert-success');

}else{
       echo html_writer::div('GoToMeeting consumer key missing', 'alert alert-danger');
 
}
echo $OUTPUT->footer();


