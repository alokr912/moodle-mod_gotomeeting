<?php
require_once 'config.php';
require_once 'OSD.php';

OSD::setup(CONSUMER_KEY);

if($_POST['action'] == 'createTraining') {
	$attributes = array();
	$attributes['name'] = $_POST['name'];
	$attributes['description'] = $_POST['desc'];
	$attributes['timeZone'] = "";
	$times = array();
	$startDate = $_POST['startDate'];
	$startTime = $_POST['startTime'];
	$endDate = $_POST['endDate'];
	$endTime = $_POST['endTime'];
	
	$arr = array();
	$arr['startDate'] = $startDate . "T" . $startTime . ":00Z";
	$arr['endDate'] = $endDate . "T" . $endTime . ":00Z";
	array_push($times, $arr);
	$attributes['times'] = $times;
	
	$key = OSD::$oauth->organizer_key;
	$response = OSD::post("/G2T/rest/organizers/{$key}/trainings/", $attributes);
	if($response->status == 201) {
		print "'{$attributes['name']}' training was created successfully";
	} else {
		print "there was an error registering the participant";
	}
}

?>