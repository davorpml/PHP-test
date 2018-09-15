<?php
require_once('ClassAction.php');
$ca = new ClassAction();
$countries = $ca->getCountryList();
?>
<html>
	<head>
		<title>Events</title>
		<script src="jquery.js"></script>
		<script src="json.js"></script>
	</head>
	<body>
		<form action="" method="post" id="eventForm">
			<select name="country" id="country">
			<?php foreach($countries as $country){ ?>
				<option value="<?php echo $country['code']; ?>"><?php echo $country['name']; ?></option>	
			<?php	} ?>
			</select>
			<br/>
			<input type="radio" name="event" id="event" value="view" checked> View<br/>
			<input type="radio" name="event" id="event" value="play"> Play<br/>
			<input type="radio" name="event" id="event" value="click"> Click<br/>
			<input type="submit" value="Submit" name="sendEvent" id="sendEvent"> 
		</form>
		<br/>
		<div id="result"></div>
	</body>
</html>