<?php
/*
To run this example you must perform some quick configuration. Follow these steps:

* Go to https://developer.citrixonline.com and create an API client id and client secret. The domain you use must be the domain you will be running these examples under (the domain "localhost" will always work).
* Open the config.php and fill in your client id and client secret
* Run this file in your browser.

 */
?>
<html>
	<head>
		<title>G2T API Sample</title>
		<script type="text/javascript" src="http://code.jquery.com/jquery-1.11.0.min.js"></script>
	</head>
<body>
<?php
if (!(version_compare(PHP_VERSION, "5.3") >= 0)) {
  throw new Exception('OSD PHP library requires PHP 5.3 or higher.');
}
  // Include the config file and the OSD library
  require_once 'config.php';
  require_once 'OSD.php';
  
  // Setup the API client reference. Client ID and Client Secrets are defined
  // as constants in config.php
  OSD::setup(CONSUMER_KEY);
  if (!OSD::is_authenticated()) {
    print "Press the 'Connect' button to connect to api platform <br><br>";
  }
  else {
	$access_token = OSD::$oauth->access_token; 
    print "You have been authenticated. Your access token is {$access_token}.<br><br>";
  }
  
?>
	<h1>G2T API Sample</h1>
	<div>
		<?php 
		$id = CONSUMER_KEY; 
		print "<a href=\"https://api.citrixonline.com/oauth/authorize?client_id={$id}\"><button>Connect</button></a>"; 
		?>
	</div>
	<div>
		<h2>Create Training</h2>
		<table>
		<tr><td>Name</td><td><input id="name" type="text" /></td></tr>
		<tr><td>Description</td><td><textarea id="desc" rows="3" cols="50"></textarea></td></tr>
		<tr><td>Start Date</td><td><input id="sd" type="date" value="<?php echo date('Y-m-d'); ?>" /> time <input id="st" type="time" value="12:00" /></td></tr>
		<tr><td>End Date</td><td><input id="ed" type="date" value="<?php echo date('Y-m-d'); ?>" /> time <input id="et" type="time" value="13:00" /></td></tr>
		</table>
		<button onclick="createTraining()">Create</button>
	</div>
	<div>
		<h2>Get Trainings</h2>
			<button onclick="getTrainings()">Get trainings</button><br/><br/>
		<div id="trainings">
		</div>
	</div>
	
<script type="text/javascript">

 function getTrainings() {
	$.ajax({
		type: 'get',
		url: 'ajax.php',
		data:{action: 'getTrainings'},
		success: function(item_list) {
			var newhtml = '<table border="2"><tr><th>Training</th><th>Description</th><th>Start Time</th><th>End Time</th><th>Organizer(s)</th><th>Registrants</th><th>Register new attendees</th></tr>';
			$.each(item_list, function(i, item) {
				newhtml += '<tr>';

				newhtml += '<td>' + item.name + '</td>';
				newhtml += '<td>' + item.description + '</td>';

				newhtml += '<td>';
				$.each(item.times, function(j, times){
					newhtml += times.startDate;
				})
				newhtml += '</td>';

				newhtml += '<td>';
				$.each(item.times, function(j, times){
					newhtml += times.endDate;
				})
				newhtml += '</td>';


				newhtml += '<td>';
				$.each(item.organizers, function(j, organizer){
					newhtml += organizer.email + '<br>';
				})
				newhtml += '</td>';

				newhtml += '<td><select id="registrants' + item.trainingKey +'"><option value="" disabled selected>No registrants</option></select><button onclick="getRegistrants(\'' + item.trainingKey + '\')">Get registrants</button></td>';

				newhtml += '<td><input id="firstName' + item.trainingKey + '" name="firstName" type="text" placeholder="Attendee first name"/>'
						+	'<input id="lastName' + item.trainingKey + '" name="lastName" type="text" placeholder="Attendee last name"/>'
						+	'<input id="email' + item.trainingKey + '" name="email" type="email" placeholder="Attendee email"/>'
						+	'<button onclick="registerParticipant(\'' + item.trainingKey + '\')">Register</button></td>';

				newhtml += '</tr>';
			})
			newhtml += '</table>';
			$('#trainings').html(newhtml);
		}

	});
 }
 
 function getRegistrants(trainingKey) {
	$.ajax({
		type: 'get',
		url: 'ajax.php',
		data: {action: 'getRegistrants', training: trainingKey},
		success: function(item_list) {
			var select = $('#registrants' + trainingKey);
			$('option', select).remove();
			$.each(item_list, function(i, item) {
				select.append(new Option(item.givenName + ' ' + item.surname, item.surName));
			})
		}

	});
 }

 function registerParticipant(trainingKey) {
    var first = $('#firstName' + trainingKey)[0].value;
    var last = $('#lastName' + trainingKey)[0].value;
    var email = $('#email' + trainingKey)[0].value;
    $.ajax({
        type: 'post',
        url: 'ajax_registerParticipant.php',
        data: {action: 'register', training: trainingKey, firstName: first, lastName: last, emailAddr: email},
        success: function() {
        	alert(first + ' ' + last + ' has been registered!');
        	$('#firstName' + trainingKey).val('');
        	$('#lastName' + trainingKey).val('');
        	$('#email' + trainingKey).val('');
        }

    });
}

function createTraining() {
	$.ajax({
		type: 'post',
		url: 'ajax_createTraining.php',
		data: {action: 'createTraining', name: $('#name')[0].value, desc: $('#desc')[0].value, startDate: $('#sd')[0].value, endDate: $('#ed')[0].value, startTime: $('#st')[0].value, endTime: $('#et')[0].value},
		success: function(html) {
			alert(html);
			$('#name').val('');
        	$('#desc').val('');
		}

	});
 }

 
</script>
</body>
</html>
