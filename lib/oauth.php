<?php
require_once 'config.php';
require_once 'OSD.php';

OSD::setup(CONSUMER_KEY);

if(isset($_GET['code'])) {
	$code = $_GET['code'];
	$response = OSD::authenticate_with_authorization_code($code);
	header("Location: /g2t/example.php");
}

exit();
?>