/**
 *process form with ajax
 *data json formated
 *insert/update event records
*/
$(document).ready(function(){
	
	//Process/submit form
	$('#eventForm').submit(function(e){
		e.preventDefault();
		//Get event
		var formData = {
			'event' : $('#event:checked').val(),
			'country' : $('#country').val()
		};
		
		$.ajax({
			type: 'POST',
			url: 'post.php',
			data: formData,
			dataType: 'json',
			encode: true
		}).done(function(data){
			console.log(data);
			if(data.success){
				$("#result").html("<span><b>"+data.success+"<b/></span>");
			}else{
				$("#result").html("<span><b>Error!<b/></span>");
			}
		});
	});
});