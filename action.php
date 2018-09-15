<html>
	<head>
		<title>Events</title>
	</head>
	<body>
		<form action="post_data.php" method="post" id="eventForm">
			<input type="radio" name="event" id="event" value="view" checked> View<br/>
			<input type="radio" name="event" id="event" value="play"> Play<br/>
			<input type="radio" name="event" id="event" value="click"> Click<br/>
			<input type="submit" value="Submit" name="sendEvent" id="sendEvent"> 
		</form>
		<br/><br/>
		<h2>Last 7 days Report</h2>
		<form action="get_data.php" method="get" id="reportForm">
			<input type="radio" name="get_type" id="get_type" value="csv" checked> CSV<br/>
			<input type="radio" name="get_type" id="get_type" value="xml"> XML<br/>
			<input type="radio" name="get_type" id="get_type" value="json"> JSON<br/>
			<input type="submit" value="Generate Data report" id="generateData">
		</form>
	</body>
</html>