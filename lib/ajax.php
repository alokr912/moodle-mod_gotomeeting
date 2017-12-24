<?php
header("content-type:application/json");
require_once 'config.php';
require_once 'OSD.php';

OSD::setup(CONSUMER_KEY);

if($_GET['action'] == 'getTrainings') {
	$key = OSD::$oauth->organizer_key;
	print OSD::get("/G2T/rest/organizers/{$key}/trainings/")->body;
}

if($_GET['action'] == 'getRegistrants') {
	$key = OSD::$oauth->organizer_key;
	$trainingKey = $_GET['training'];
	print OSD::get("/G2T/rest/organizers/{$key}/trainings/{$trainingKey}/registrants/")->body;
}
exit();
?>