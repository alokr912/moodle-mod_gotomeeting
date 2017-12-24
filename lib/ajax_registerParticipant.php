<?php
require_once 'config.php';
require_once 'OSD.php';

OSD::setup(CONSUMER_KEY);

if($_POST['action'] == 'register') {
	$attributes = array();
	$attributes['email'] = $_POST['emailAddr'];
	$attributes['givenName'] = $_POST['firstName'];
	$attributes['surname'] = $_POST['lastName'];
	
	$key = OSD::$oauth->organizer_key;
	$trainingKey = $_POST['training'];
	$response = OSD::post("/G2T/rest/organizers/{$key}/trainings/{$trainingKey}/registrants/", $attributes);
	if($response->status == 201) {
		print "{$attributes['givenName']} {$attributes['surname']} was registered successfully";
	} else {
		print "there was an error registering the participant";
	}
}

?>